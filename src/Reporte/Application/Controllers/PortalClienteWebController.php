<?php

namespace Src\Reporte\Application\Controllers;

use App\Http\Controllers\Controller;
use App\Support\InertiaTablePaginator;
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

        $paginator = VehiculoEloquentModel::whereIn('cliente_id', $clienteIds)
            ->orderBy('placa')
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn ($v) => [
                'id' => $v->id,
                'placa' => $v->placa,
                'marca' => $v->marca,
                'modelo' => $v->modelo,
                'anio' => $v->anio,
                'color' => $v->color,
                'kilometraje' => $v->kilometraje,
                'activo' => (bool) $v->activo,
            ]);

        return Inertia::render('PortalCliente/vehiculos', [
            'vehiculos' => InertiaTablePaginator::make($paginator),
        ]);
    }

    public function misOrdenes(): Response
    {
        $clienteIds = $this->clienteIdsDelUsuario();

        $paginator = OrdenTrabajoEloquentModel::with(['vehiculo', 'pago'])
            ->whereIn('cliente_id', $clienteIds)
            ->orderByDesc('created_at')
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn ($orden) => [
                'id' => $orden->id,
                'numero' => $orden->numero,
                'estado' => $orden->estado instanceof \BackedEnum ? $orden->estado->value : $orden->estado,
                'estadoLabel' => $orden->estado instanceof \BackedEnum ? $orden->estado->label() : $orden->estado,
                'vehiculoPlaca' => $orden->vehiculo?->placa,
                'tipoFalla' => $orden->tipo_falla,
                'prioridad' => $orden->prioridad,
                'totalPago' => $orden->pago ? (float) $orden->pago->total : null,
                'createdAt' => $orden->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('PortalCliente/ordenes', [
            'ordenes' => InertiaTablePaginator::make($paginator),
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
