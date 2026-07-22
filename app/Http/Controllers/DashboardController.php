<?php

namespace App\Http\Controllers;

use App\Enums\FacturaEstado;
use App\Enums\OrdenEstado;
use App\Enums\PagoEstado;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Pago\Infrastructure\Models\PagoEloquentModel;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user?->hasRole(UserRole::Cliente)) {
            $clienteIds = ClienteEloquentModel::query()
                ->where('user_id', $user->id)
                ->pluck('id');

            $ordenesRecientes = OrdenTrabajoEloquentModel::with(['cliente', 'vehiculo'])
                ->whereIn('cliente_id', $clienteIds)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn ($o) => $this->mapOrden($o))
                ->toArray();

            $abiertas = OrdenTrabajoEloquentModel::query()
                ->whereIn('cliente_id', $clienteIds)
                ->whereNotIn('estado', [
                    OrdenEstado::Finalizada->value,
                    OrdenEstado::Entregada->value,
                    OrdenEstado::Cancelada->value,
                ])
                ->count();

            return Inertia::render('Dashboard', [
                'metrics' => [
                    'ordenesAbiertas' => $abiertas,
                    'facturasPendientes' => 0,
                    'ingresosMes' => 0,
                    'clientesActivos' => 0,
                ],
                'ordenesRecientes' => $ordenesRecientes,
                'vista' => 'cliente',
            ]);
        }

        $ordenQuery = OrdenTrabajoEloquentModel::query();
        if ($user?->hasRole(UserRole::Mecanico)) {
            $mecanicoId = $user->mecanico?->id;
            $ordenQuery->where('mecanico_id', $mecanicoId);
        }

        $ordenesAbiertas = (clone $ordenQuery)
            ->whereNotIn('estado', [
                OrdenEstado::Finalizada->value,
                OrdenEstado::Entregada->value,
                OrdenEstado::Cancelada->value,
            ])
            ->count();

        $facturasPendientes = 0;
        $ingresosMes = 0.0;
        $clientesActivos = 0;

        if ($user?->hasRole(UserRole::Administrador, UserRole::Recepcionista)) {
            $facturasPendientes = FacturaEloquentModel::query()
                ->whereIn('estado', [FacturaEstado::Borrador->value, FacturaEstado::Emitida->value])
                ->count();

            $ingresosMes = (float) PagoEloquentModel::query()
                ->where('estado', PagoEstado::Pagado->value)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total');

            $clientesActivos = ClienteEloquentModel::query()->where('estado', true)->count();
        }

        $ordenesRecientes = (clone $ordenQuery)
            ->with(['cliente', 'vehiculo'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($o) => $this->mapOrden($o))
            ->toArray();

        return Inertia::render('Dashboard', [
            'metrics' => [
                'ordenesAbiertas' => $ordenesAbiertas,
                'facturasPendientes' => $facturasPendientes,
                'ingresosMes' => $ingresosMes,
                'clientesActivos' => $clientesActivos,
            ],
            'ordenesRecientes' => $ordenesRecientes,
            'vista' => $user?->hasRole(UserRole::Mecanico) ? 'mecanico' : 'taller',
        ]);
    }

    private function mapOrden(OrdenTrabajoEloquentModel $o): array
    {
        return [
            'id' => $o->id,
            'numero' => $o->numero,
            'estado' => $o->estado instanceof \BackedEnum ? $o->estado->value : $o->estado,
            'estadoLabel' => $o->estado instanceof \BackedEnum ? $o->estado->label() : $o->estado,
            'clienteNombre' => $o->cliente?->razon_social,
            'vehiculoPlaca' => $o->vehiculo?->placa,
        ];
    }
}
