<?php

namespace Src\Reporte\Application\Controllers;

use App\Http\Controllers\Controller;
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

        $vehiculos = $query->get()->map(fn (VehiculoEloquentModel $v) => [
            'id' => $v->id,
            'placa' => $v->placa,
            'marca' => $v->marca,
            'modelo' => $v->modelo,
            'anio' => $v->anio,
            'clienteNombre' => $v->cliente?->razon_social,
            'ordenesCount' => (int) $v->ordenes_trabajo_count,
        ])->toArray();

        return Inertia::render('Historial/index', [
            'vehiculos' => [
                'data' => $vehiculos,
                'meta' => ['total' => count($vehiculos)],
            ],
            'filters' => ['q' => $busqueda],
        ]);
    }

    public function show(string $vehiculoId): Response
    {
        $vehiculo = VehiculoEloquentModel::with('cliente')->findOrFail($vehiculoId);

        $ordenes = OrdenTrabajoEloquentModel::with(['mecanico', 'pago', 'factura'])
            ->where('vehiculo_id', $vehiculoId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($orden) => [
                'id' => $orden->id,
                'numero' => $orden->numero,
                'estado' => $orden->estado instanceof \BackedEnum ? $orden->estado->value : $orden->estado,
                'estadoLabel' => $orden->estado instanceof \BackedEnum ? $orden->estado->label() : $orden->estado,
                'tipoFalla' => $orden->tipo_falla,
                'fallaReportada' => $orden->falla_reportada,
                'kilometrajeIngreso' => $orden->kilometraje_ingreso,
                'mecanicoNombre' => $orden->mecanico
                    ? trim(($orden->mecanico->nombres ?? '') . ' ' . ($orden->mecanico->apellidos ?? ''))
                    : null,
                'totalPago' => $orden->pago ? (float) $orden->pago->total : null,
                'facturaNumero' => $orden->factura?->numero,
                'createdAt' => $orden->created_at?->format('Y-m-d H:i:s'),
            ])->toArray();

        return Inertia::render('Historial/show', [
            'vehiculo' => [
                'id' => $vehiculo->id,
                'placa' => $vehiculo->placa,
                'marca' => $vehiculo->marca,
                'modelo' => $vehiculo->modelo,
                'anio' => $vehiculo->anio,
                'clienteNombre' => $vehiculo->cliente?->razon_social,
            ],
            'ordenes' => $ordenes,
        ]);
    }
}
