<?php

namespace Src\Reporte\Application\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class HistorialWebController extends Controller
{
    public function show(string $vehiculoId): Response
    {
        $vehiculo = VehiculoEloquentModel::with('cliente')->findOrFail($vehiculoId);

        $ordenes = OrdenTrabajoEloquentModel::with(['mecanico', 'pago'])
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
