<?php

namespace Src\Reporte\Application\Controllers;

use App\Http\Controllers\Controller;
use App\Support\InertiaTablePaginator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class HistorialWebController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = trim((string) $request->query('q', ''));

        $query = VehiculoEloquentModel::with('cliente')
            ->withCount('ordenesTrabajo')
            ->orderBy('placa');

        if ($busqueda !== '') {
            $term = '%' . mb_strtolower($busqueda) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(placa) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(marca) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(modelo) LIKE ?', [$term])
                    ->orWhereHas('cliente', function ($cq) use ($term) {
                        $cq->whereRaw('LOWER(razon_social) LIKE ?', [$term]);
                    });
            });
        }

        $paginator = $query
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn (VehiculoEloquentModel $v) => [
                'id' => $v->id,
                'placa' => $v->placa,
                'marca' => $v->marca,
                'modelo' => $v->modelo,
                'anio' => $v->anio,
                'clienteNombre' => $v->cliente?->razon_social,
                'ordenesCount' => (int) $v->ordenes_trabajo_count,
            ]);

        return Inertia::render('Historial/index', [
            'vehiculos' => InertiaTablePaginator::make($paginator),
            'filters' => ['q' => $busqueda],
        ]);
    }

    public function show(string $vehiculoId): Response
    {
        $vehiculo = VehiculoEloquentModel::with('cliente')->findOrFail($vehiculoId);

        $paginator = OrdenTrabajoEloquentModel::with([
            'cliente',
            'vehiculo',
            'mecanico',
            'pago',
            'factura',
            'ordenServicios.servicio',
            'ordenRepuestos.producto',
            'avances.user',
            'sugerenciaIa',
        ])
            ->where('vehiculo_id', $vehiculoId)
            ->orderByDesc('created_at')
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn ($orden) => $this->mapOrdenHistorial($orden));

        return Inertia::render('Historial/show', [
            'vehiculo' => [
                'id' => $vehiculo->id,
                'placa' => $vehiculo->placa,
                'marca' => $vehiculo->marca,
                'modelo' => $vehiculo->modelo,
                'anio' => $vehiculo->anio,
                'clienteNombre' => $vehiculo->cliente?->razon_social,
            ],
            'ordenes' => InertiaTablePaginator::make($paginator),
        ]);
    }

    private function mapOrdenHistorial(OrdenTrabajoEloquentModel $orden): array
    {
        $sugerencia = $orden->sugerenciaIa;
        $input = $sugerencia && is_array($sugerencia->input_data) ? $sugerencia->input_data : [];
        $factura = $orden->factura;
        $pago = $orden->pago;

        return [
            'id' => $orden->id,
            'numero' => $orden->numero,
            'estado' => $orden->estado instanceof \BackedEnum ? $orden->estado->value : $orden->estado,
            'estadoLabel' => $orden->estado instanceof \BackedEnum ? $orden->estado->label() : $orden->estado,
            'tipoFalla' => $orden->tipo_falla,
            'fallaReportada' => $orden->falla_reportada,
            'diagnosticoTecnico' => $orden->diagnostico_tecnico,
            'prioridad' => $orden->prioridad,
            'observaciones' => $orden->observaciones,
            'kilometrajeIngreso' => $orden->kilometraje_ingreso,
            'mecanicoNombre' => $orden->mecanico
                ? trim(($orden->mecanico->nombres ?? '') . ' ' . ($orden->mecanico->apellidos ?? ''))
                : null,
            'servicios' => $orden->ordenServicios->map(fn ($os) => [
                'nombre' => $os->servicio?->nombre ?? 'Servicio',
                'precio' => (float) $os->precio,
            ])->values()->toArray(),
            'repuestos' => $orden->ordenRepuestos->map(fn ($or) => [
                'nombre' => $or->producto?->nombre ?? 'Repuesto',
                'cantidad' => (int) $or->cantidad,
                'precioUnitario' => (float) $or->precio_unitario,
                'subtotal' => round((float) $or->precio_unitario * (int) $or->cantidad, 2),
            ])->values()->toArray(),
            'avances' => $orden->avances->map(fn ($avance) => [
                'mensaje' => $avance->mensaje,
                'usuarioNombre' => $avance->user?->name ?? 'Usuario',
                'createdAt' => $avance->created_at?->format('Y-m-d H:i:s'),
            ])->values()->toArray(),
            'sugerenciaIa' => $sugerencia ? [
                'estadoLabel' => $sugerencia->estado instanceof \BackedEnum
                    ? $sugerencia->estado->label()
                    : $sugerencia->estado,
                'esSimulado' => (bool) $sugerencia->es_simulado,
                'diagnosticoDetalle' => $sugerencia->diagnostico_detalle,
                'posiblesCausas' => $sugerencia->posibles_causas ?? [],
                'accionesRecomendadas' => $sugerencia->acciones_recomendadas ?? [],
                'especialidadRecomendada' => $sugerencia->especialidad_recomendada,
                'servicioRecomendado' => $sugerencia->servicio_recomendado,
                'serviciosSugeridos' => $input['servicios_sugeridos'] ?? [],
                'repuestosSugeridos' => $input['repuestos_sugeridos'] ?? [],
                'prioridad' => $sugerencia->prioridad,
                'observacionMecanico' => $sugerencia->observacion_mecanico,
                'advertencia' => $sugerencia->advertencia,
                'coincideAnalisis' => $sugerencia->coincide_analisis,
                'observacionesRevision' => $sugerencia->observaciones_revision,
                'respuestaCompleta' => $sugerencia->respuesta_completa,
                'createdAt' => $sugerencia->created_at?->format('Y-m-d H:i:s'),
            ] : null,
            'factura' => $factura ? [
                'id' => $factura->id,
                'numero' => $factura->numero,
                'serie' => $factura->serie,
                'fechaEmision' => $factura->fecha_emision?->format('Y-m-d'),
                'subtotal' => (float) $factura->subtotal,
                'iva' => (float) $factura->iva,
                'descuento' => (float) $factura->descuento,
                'total' => (float) $factura->total,
                'estado' => $factura->estado?->value ?? $factura->estado,
                'estadoLabel' => $factura->estado?->label(),
            ] : null,
            'pago' => $pago ? [
                'id' => $pago->id,
                'total' => (float) $pago->total,
                'estado' => $pago->estado->value,
                'estadoLabel' => $pago->estado->label(),
                'metodoPago' => $pago->metodo_pago,
            ] : null,
            'createdAt' => $orden->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
