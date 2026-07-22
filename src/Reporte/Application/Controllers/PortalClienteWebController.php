<?php

namespace Src\Reporte\Application\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class PortalClienteWebController extends Controller
{
    public function misVehiculos(): Response
    {
        $clienteIds = $this->clienteIdsDelUsuario();

        $vehiculos = VehiculoEloquentModel::whereIn('cliente_id', $clienteIds)
            ->orderBy('placa')
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'placa' => $v->placa,
                'marca' => $v->marca,
                'modelo' => $v->modelo,
                'anio' => $v->anio,
                'color' => $v->color,
                'kilometraje' => $v->kilometraje,
                'activo' => (bool) $v->activo,
            ])->toArray();

        return Inertia::render('PortalCliente/vehiculos', [
            'vehiculos' => $vehiculos,
        ]);
    }

    public function misOrdenes(): Response
    {
        $clienteIds = $this->clienteIdsDelUsuario();

        $ordenes = OrdenTrabajoEloquentModel::with(['vehiculo', 'pago'])
            ->whereIn('cliente_id', $clienteIds)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($orden) => [
                'id' => $orden->id,
                'numero' => $orden->numero,
                'estado' => $orden->estado instanceof \BackedEnum ? $orden->estado->value : $orden->estado,
                'estadoLabel' => $orden->estado instanceof \BackedEnum ? $orden->estado->label() : $orden->estado,
                'vehiculoPlaca' => $orden->vehiculo?->placa,
                'tipoFalla' => $orden->tipo_falla,
                'prioridad' => $orden->prioridad,
                'totalPago' => $orden->pago ? (float) $orden->pago->total : null,
                'createdAt' => $orden->created_at?->format('Y-m-d H:i:s'),
            ])->toArray();

        return Inertia::render('PortalCliente/ordenes', [
            'ordenes' => $ordenes,
        ]);
    }

    /** @return list<string> */
    private function clienteIdsDelUsuario(): array
    {
        $userId = auth()->id();

        return ClienteEloquentModel::where('user_id', $userId)
            ->pluck('id')
            ->toArray();
    }
}
