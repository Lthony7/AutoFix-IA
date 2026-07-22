<?php

namespace Src\Reporte\Application\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\DiagnosticoIA\Infrastructure\Models\DiagnosticoIaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenServicioEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Pago\Infrastructure\Models\PagoEloquentModel;

class ReporteWebController extends Controller
{
    public function index(): Response
    {
        $ordenesPorEstado = OrdenTrabajoEloquentModel::query()
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get()
            ->map(fn ($row) => [
                'estado' => $row->estado instanceof \BackedEnum ? $row->estado->value : $row->estado,
                'label' => $row->estado instanceof \BackedEnum ? $row->estado->label() : $row->estado,
                'total' => (int) $row->total,
            ])->toArray();

        $ingresosPorFecha = PagoEloquentModel::query()
            ->where('estado', 'pagado')
            ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('SUM(total) as total'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderByDesc('fecha')
            ->limit(30)
            ->get()
            ->map(fn ($row) => [
                'fecha' => $row->fecha,
                'total' => (float) $row->total,
            ])->toArray();

        $serviciosTop = OrdenServicioEloquentModel::query()
            ->join('servicios', 'orden_servicio.servicio_id', '=', 'servicios.id')
            ->select('servicios.nombre', DB::raw('count(*) as total'), DB::raw('SUM(orden_servicio.precio) as ingresos'))
            ->groupBy('servicios.id', 'servicios.nombre')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'nombre' => $row->nombre,
                'total' => (int) $row->total,
                'ingresos' => (float) $row->ingresos,
            ])->toArray();

        $sugerenciasIa = DiagnosticoIaEloquentModel::query()
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get()
            ->map(fn ($row) => [
                'estado' => $row->estado instanceof \BackedEnum ? $row->estado->value : $row->estado,
                'label' => $row->estado instanceof \BackedEnum ? $row->estado->label() : $row->estado,
                'total' => (int) $row->total,
            ])->toArray();

        $simulados = DiagnosticoIaEloquentModel::where('es_simulado', true)->count();
        $reales = DiagnosticoIaEloquentModel::where('es_simulado', false)->count();

        return Inertia::render('Reporte/index', [
            'stats' => [
                'ordenesPorEstado' => $ordenesPorEstado,
                'ingresosPorFecha' => $ingresosPorFecha,
                'serviciosTop' => $serviciosTop,
                'sugerenciasIa' => $sugerenciasIa,
                'sugerenciasIaResumen' => [
                    'simulados' => $simulados,
                    'reales' => $reales,
                    'total' => $simulados + $reales,
                ],
                'totalOrdenes' => OrdenTrabajoEloquentModel::count(),
                'totalIngresos' => (float) PagoEloquentModel::where('estado', 'pagado')->sum('total'),
            ],
        ]);
    }
}
