<?php

namespace Src\Reporte\Application\Controllers;

use App\Http\Controllers\Controller;
use App\Support\InertiaTablePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Reporte\Infrastructure\Requests\UpdatePortalClienteRequest;
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

    public function historial(): Response
    {
        $clienteIds = $this->clienteIdsDelUsuario();

        $paginator = OrdenTrabajoEloquentModel::with([
            'vehiculo',
            'ordenServicios.servicio',
            'avances',
        ])
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
                'vehiculoDescripcion' => $orden->vehiculo
                    ? trim(($orden->vehiculo->marca ?? '') . ' ' . ($orden->vehiculo->modelo ?? ''))
                    : null,
                'tipoFalla' => $orden->tipo_falla,
                'diagnosticoTecnico' => $orden->diagnostico_tecnico,
                'kilometrajeIngreso' => $orden->kilometraje_ingreso,
                'servicios' => $orden->ordenServicios->map(fn ($os) => [
                    'nombre' => $os->servicio?->nombre ?? 'Servicio',
                    'precio' => (float) $os->precio,
                ])->values()->toArray(),
                'avancesRecientes' => $orden->avances->take(3)->map(fn ($avance) => [
                    'mensaje' => $avance->mensaje,
                    'createdAt' => $avance->created_at?->format('Y-m-d H:i:s'),
                ])->values()->toArray(),
                'createdAt' => $orden->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('PortalCliente/historial', [
            'historial' => InertiaTablePaginator::make($paginator),
        ]);
    }

    public function misDatos(): Response
    {
        $cliente = $this->clienteDelUsuario();

        return Inertia::render('PortalCliente/perfil', [
            'cliente' => $cliente ? [
                'id' => $cliente->id,
                'nombres' => $cliente->nombres ?? '',
                'apellidos' => $cliente->apellidos ?? '',
                'telefono' => $cliente->telefono ?? '',
                'email' => $cliente->email ?? '',
                'direccion' => $cliente->direccion ?? '',
                'numeroDocumento' => $cliente->numero_documento,
                'tipoDocumento' => $cliente->tipo_documento,
            ] : null,
        ]);
    }

    public function actualizarDatos(UpdatePortalClienteRequest $request): RedirectResponse
    {
        $cliente = $this->clienteDelUsuario();

        if (!$cliente) {
            return redirect()
                ->route('portal.mis-datos')
                ->with('error', 'No hay una ficha de cliente vinculada a tu usuario.');
        }

        $data = $request->validated();
        $razonSocial = trim(($data['nombres'] ?? '') . ' ' . ($data['apellidos'] ?? ''));

        DB::transaction(function () use ($cliente, $data, $razonSocial, $request) {
            $cliente->update([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'razon_social' => $razonSocial !== '' ? $razonSocial : $cliente->razon_social,
                'telefono' => $data['telefono'],
                'email' => $data['email'],
                'direccion' => $data['direccion'],
            ]);

            /** @var UserEloquentModel|null $user */
            $user = $request->user();
            if ($user) {
                $user->update([
                    'name' => $razonSocial !== '' ? $razonSocial : $user->name,
                    'email' => $data['email'],
                ]);
            }
        });

        return redirect()
            ->route('portal.mis-datos')
            ->with('success', 'Datos actualizados correctamente');
    }

    /** @return list<string> */
    private function clienteIdsDelUsuario(): array
    {
        $userId = auth()->id();

        return ClienteEloquentModel::where('user_id', $userId)
            ->pluck('id')
            ->toArray();
    }

    private function clienteDelUsuario(): ?ClienteEloquentModel
    {
        return ClienteEloquentModel::where('user_id', auth()->id())->first();
    }
}
