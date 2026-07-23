<?php

namespace Src\Reporte\Application\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\DiagnosticoIA\Infrastructure\Models\DiagnosticoIaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenRepuestoEloquentModel;
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

        $repuestosTop = OrdenRepuestoEloquentModel::query()
            ->join('productos', 'orden_repuesto.producto_id', '=', 'productos.id')
            ->select(
                'productos.nombre',
                DB::raw('SUM(orden_repuesto.cantidad) as cantidad'),
                DB::raw('COUNT(DISTINCT orden_repuesto.orden_trabajo_id) as ordenes'),
                DB::raw('SUM(orden_repuesto.cantidad * orden_repuesto.precio_unitario) as ingresos')
            )
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('cantidad')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'nombre' => $row->nombre,
                'cantidad' => (int) $row->cantidad,
                'ordenes' => (int) $row->ordenes,
                'ingresos' => (float) $row->ingresos,
            ])->toArray();

        $vehiculosPorCliente = OrdenTrabajoEloquentModel::query()
            ->join('clientes', 'ordenes_trabajo.cliente_id', '=', 'clientes.id')
            ->select(
                'clientes.razon_social as cliente',
                DB::raw('COUNT(DISTINCT ordenes_trabajo.vehiculo_id) as vehiculos'),
                DB::raw('COUNT(ordenes_trabajo.id) as ordenes')
            )
            ->groupBy('clientes.id', 'clientes.razon_social')
            ->orderByDesc('ordenes')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'cliente' => $row->cliente,
                'vehiculos' => (int) $row->vehiculos,
                'ordenes' => (int) $row->ordenes,
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
                'repuestosTop' => $repuestosTop,
                'vehiculosPorCliente' => $vehiculosPorCliente,
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
