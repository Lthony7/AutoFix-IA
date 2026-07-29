<?php

namespace Src\Reporte\Application\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Reporte\Application\Services\ReporteDatosService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteWebController extends Controller
{
    public function __construct(
        private readonly ReporteDatosService $datos
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Reporte/index', [
            'stats' => $this->datos->recopilar(),
        ]);
    }

    public function exportExcel(): StreamedResponse
    {
        $stats = $this->datos->recopilar();
        $filename = 'reporte-taller-' . now()->format('Y-m-d-His') . '.xls';

        return response()->streamDownload(function () use ($stats) {
            echo $this->buildExcelXml($stats);
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function exportPdf(): HttpResponse
    {
        $stats = $this->datos->recopilar();
        $filename = 'reporte-taller-' . now()->format('Y-m-d-His') . '.pdf';

        $pdf = Pdf::loadView('reportes.taller', ['stats' => $stats])
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function buildExcelXml(array $stats): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
            . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        $xml .= $this->excelSheet('Resumen', [
            ['Métrica', 'Valor'],
            ['Total órdenes', $stats['totalOrdenes'] ?? 0],
            ['Ingresos pagados', $stats['totalIngresos'] ?? 0],
            ['Diagnósticos IA', $stats['sugerenciasIaResumen']['total'] ?? 0],
            ['IA simulados', $stats['sugerenciasIaResumen']['simulados'] ?? 0],
            ['IA reales', $stats['sugerenciasIaResumen']['reales'] ?? 0],
            ['Inventario ítems', $stats['inventarioResumen']['totalItems'] ?? 0],
            ['Inventario activos', $stats['inventarioResumen']['activos'] ?? 0],
            ['Stock bajo', $stats['inventarioResumen']['stockBajo'] ?? 0],
            ['Sin stock', $stats['inventarioResumen']['sinStock'] ?? 0],
            ['Valor stock', $stats['inventarioResumen']['valorStock'] ?? 0],
            ['Generado en', $stats['generadoEn'] ?? ''],
        ]);

        $ordenesRows = [['Estado', 'Total']];
        foreach ($stats['ordenesPorEstado'] ?? [] as $row) {
            $ordenesRows[] = [$row['label'], $row['total']];
        }
        $xml .= $this->excelSheet('Ordenes por estado', $ordenesRows);

        $iaRows = [['Estado', 'Total']];
        foreach ($stats['sugerenciasIa'] ?? [] as $row) {
            $iaRows[] = [$row['label'], $row['total']];
        }
        $xml .= $this->excelSheet('Sugerencias IA', $iaRows);

        $ingresosRows = [['Fecha', 'Total']];
        foreach ($stats['ingresosPorFecha'] ?? [] as $row) {
            $ingresosRows[] = [$row['fecha'], $row['total']];
        }
        $xml .= $this->excelSheet('Ingresos por fecha', $ingresosRows);

        $serviciosRows = [['Servicio', 'Cantidad', 'Ingresos']];
        foreach ($stats['serviciosTop'] ?? [] as $row) {
            $serviciosRows[] = [$row['nombre'], $row['total'], $row['ingresos']];
        }
        $xml .= $this->excelSheet('Servicios top', $serviciosRows);

        $repuestosRows = [['Item inventario', 'Cantidad', 'Ordenes', 'Ingresos']];
        foreach ($stats['repuestosTop'] ?? [] as $row) {
            $repuestosRows[] = [$row['nombre'], $row['cantidad'], $row['ordenes'], $row['ingresos']];
        }
        $xml .= $this->excelSheet('Inventario mas usado', $repuestosRows);

        $stockBajoRows = [['Codigo', 'Nombre', 'Categoria', 'Stock', 'Minimo', 'Precio']];
        foreach ($stats['stockBajo'] ?? [] as $row) {
            $stockBajoRows[] = [
                $row['codigo'],
                $row['nombre'],
                $row['categoria'] ?? '',
                $row['stock'],
                $row['stockMinimo'],
                $row['precio'],
            ];
        }
        $xml .= $this->excelSheet('Stock bajo', $stockBajoRows);

        $clientesRows = [['Cliente', 'Vehiculos', 'Ordenes']];
        foreach ($stats['vehiculosPorCliente'] ?? [] as $row) {
            $clientesRows[] = [$row['cliente'], $row['vehiculos'], $row['ordenes']];
        }
        $xml .= $this->excelSheet('Clientes', $clientesRows);

        $xml .= '</Workbook>';

        return $xml;
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function excelSheet(string $name, array $rows): string
    {
        $safeName = htmlspecialchars(mb_substr(preg_replace('/[\\\\\/\?\*\[\]]/', '', $name) ?: 'Hoja', 0, 31), ENT_XML1);
        $xml = '<Worksheet ss:Name="' . $safeName . '"><Table>' . "\n";

        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach ($row as $cell) {
                if (is_int($cell) || is_float($cell)) {
                    $xml .= '<Cell><Data ss:Type="Number">' . $cell . '</Data></Cell>';
                } else {
                    $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars((string) $cell, ENT_XML1) . '</Data></Cell>';
                }
            }
            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table></Worksheet>' . "\n";

        return $xml;
    }
}
