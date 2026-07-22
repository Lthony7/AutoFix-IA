<?php

namespace Src\OrdenTrabajo\Application\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenRepuestoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenServicioEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Requests\AsignarMecanicoRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\CambiarEstadoOrdenRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\StoreOrdenTrabajoRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\UpdateOrdenTrabajoRequest;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class OrdenTrabajoWebController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = OrdenTrabajoEloquentModel::with([
            'cliente',
            'vehiculo',
            'mecanico',
            'factura',
            'ordenServicios',
            'ordenRepuestos',
        ])->orderByDesc('created_at');

        if ($user->hasRole(UserRole::Mecanico)) {
            $mecanicoId = $user->mecanico?->id;
            $query->where('mecanico_id', $mecanicoId);
        }

        $ordenes = $query->get()->map(fn ($orden) => $this->mapOrden($orden))->toArray();

        return Inertia::render('OrdenTrabajo/index', [
            'ordenes' => [
                'data' => $ordenes,
                'meta' => ['total' => count($ordenes)],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('OrdenTrabajo/create', [
            'clientes' => $this->clientesOptions(),
            'vehiculos' => $this->vehiculosOptions(),
            'mecanicos' => $this->mecanicosOptions(),
            'servicios' => $this->serviciosOptions(),
            'repuestos' => $this->repuestosOptions(),
        ]);
    }

    public function store(StoreOrdenTrabajoRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $data = $request->validated();
                unset($data['servicios'], $data['repuestos']);

                $orden = OrdenTrabajoEloquentModel::create(array_merge($data, [
                    'numero' => OrdenTrabajoEloquentModel::generarNumero(),
                ]));

                $this->syncServicios($orden, $request->input('servicios', []));
                $this->syncRepuestos($orden, $request->input('repuestos', []));
            });

            return redirect()->route('ordenes.index')->with('success', 'Orden de trabajo creada exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error al crear la orden: ' . $e->getMessage());
        }
    }

    public function edit(Request $request, string $id): Response
    {
        $orden = $this->findOrdenForUser($request, $id);
        $orden->load(['cliente', 'vehiculo', 'mecanico', 'ordenServicios.servicio', 'ordenRepuestos.producto']);

        return Inertia::render('OrdenTrabajo/edit', [
            'orden' => $this->mapOrden($orden, true),
            'clientes' => $this->clientesOptions(),
            'vehiculos' => $this->vehiculosOptions(),
            'mecanicos' => $this->mecanicosOptions(),
            'servicios' => $this->serviciosOptions(),
            'repuestos' => $this->repuestosOptions(),
            'soloDiagnostico' => $request->user()->hasRole(UserRole::Mecanico),
        ]);
    }

    public function update(UpdateOrdenTrabajoRequest $request, string $id): RedirectResponse
    {
        try {
            $orden = $this->findOrdenForUser($request, $id);

            DB::transaction(function () use ($request, $orden) {
                $data = $request->validated();
                $servicios = $data['servicios'] ?? null;
                $repuestos = $data['repuestos'] ?? null;
                unset($data['servicios'], $data['repuestos']);

                if ($request->user()->hasRole(UserRole::Mecanico)) {
                    $orden->update(array_filter([
                        'diagnostico_tecnico' => $data['diagnostico_tecnico'] ?? null,
                        'observaciones' => $data['observaciones'] ?? null,
                    ], fn ($v) => $v !== null));
                } else {
                    if ($data !== []) {
                        $orden->update($data);
                    }

                    if ($servicios !== null) {
                        $orden->ordenServicios()->delete();
                        $this->syncServicios($orden, $servicios);
                    }

                    if ($repuestos !== null) {
                        $orden->ordenRepuestos()->delete();
                        $this->syncRepuestos($orden, $repuestos);
                    }
                }
            });

            return redirect()->route('ordenes.index')->with('success', 'Orden actualizada exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error al actualizar la orden: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $orden = OrdenTrabajoEloquentModel::find($id);

        if (!$orden) {
            return redirect()->back()->with('error', 'Orden no encontrada');
        }

        if ($request->user()->hasRole(UserRole::Mecanico)) {
            abort(403, 'No tienes permiso para eliminar órdenes.');
        }

        $orden->delete();

        return redirect()->route('ordenes.index')->with('success', 'Orden eliminada exitosamente');
    }

    public function asignarMecanico(AsignarMecanicoRequest $request, string $id): RedirectResponse
    {
        $orden = OrdenTrabajoEloquentModel::findOrFail($id);
        $orden->update(['mecanico_id' => $request->validated('mecanico_id')]);

        return redirect()->back()->with('success', 'Mecánico asignado exitosamente');
    }

    public function cambiarEstado(CambiarEstadoOrdenRequest $request, string $id): RedirectResponse
    {
        $orden = OrdenTrabajoEloquentModel::findOrFail($id);
        $orden->update(['estado' => $request->validated('estado')]);

        return redirect()->back()->with('success', 'Estado de la orden actualizado');
    }

    private function findOrdenForUser(Request $request, string $id): OrdenTrabajoEloquentModel
    {
        $orden = OrdenTrabajoEloquentModel::findOrFail($id);

        if ($request->user()->hasRole(UserRole::Mecanico)) {
            $mecanicoId = $request->user()->mecanico?->id;
            if ($orden->mecanico_id !== $mecanicoId) {
                abort(403, 'No tienes acceso a esta orden.');
            }
        }

        return $orden;
    }

    private function mapOrden(OrdenTrabajoEloquentModel $orden, bool $detailed = false): array
    {
        $data = [
            'id' => $orden->id,
            'numero' => $orden->numero,
            'clienteId' => $orden->cliente_id,
            'vehiculoId' => $orden->vehiculo_id,
            'mecanicoId' => $orden->mecanico_id,
            'estado' => $orden->estado instanceof \BackedEnum ? $orden->estado->value : $orden->estado,
            'estadoLabel' => $orden->estado instanceof \BackedEnum ? $orden->estado->label() : $orden->estado,
            'tipoFalla' => $orden->tipo_falla,
            'fallaReportada' => $orden->falla_reportada,
            'kilometrajeIngreso' => $orden->kilometraje_ingreso,
            'observaciones' => $orden->observaciones,
            'diagnosticoTecnico' => $orden->diagnostico_tecnico,
            'prioridad' => $orden->prioridad,
            'clienteNombre' => $orden->cliente?->razon_social,
            'vehiculoPlaca' => $orden->vehiculo?->placa,
            'mecanicoNombre' => $orden->mecanico
                ? trim(($orden->mecanico->nombres ?? '') . ' ' . ($orden->mecanico->apellidos ?? ''))
                : null,
            'facturaId' => $orden->factura?->id,
            'puedeFacturar' => !$orden->factura
                && ($orden->ordenServicios->isNotEmpty() || $orden->ordenRepuestos->isNotEmpty()),
            'createdAt' => $orden->created_at?->format('Y-m-d H:i:s'),
        ];

        if ($detailed) {
            $data['servicios'] = $orden->ordenServicios->map(fn ($os) => [
                'id' => $os->id,
                'servicioId' => $os->servicio_id,
                'servicioNombre' => $os->servicio?->nombre,
                'precio' => (float) $os->precio,
            ])->toArray();

            $data['repuestos'] = $orden->ordenRepuestos->map(fn ($or) => [
                'id' => $or->id,
                'productoId' => $or->producto_id,
                'productoNombre' => $or->producto?->nombre,
                'cantidad' => $or->cantidad,
                'precioUnitario' => (float) $or->precio_unitario,
            ])->toArray();
        }

        return $data;
    }

    private function syncServicios(OrdenTrabajoEloquentModel $orden, array $servicios): void
    {
        foreach ($servicios as $item) {
            OrdenServicioEloquentModel::create([
                'orden_trabajo_id' => $orden->id,
                'servicio_id' => $item['servicioId'] ?? $item['servicio_id'],
                'precio' => $item['precio'],
            ]);
        }
    }

    private function syncRepuestos(OrdenTrabajoEloquentModel $orden, array $repuestos): void
    {
        foreach ($repuestos as $item) {
            OrdenRepuestoEloquentModel::create([
                'orden_trabajo_id' => $orden->id,
                'producto_id' => $item['productoId'] ?? $item['producto_id'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precioUnitario'] ?? $item['precio_unitario'],
            ]);
        }
    }

    private function clientesOptions(): array
    {
        return ClienteEloquentModel::where('estado', true)->orderBy('razon_social')->get()
            ->map(fn ($c) => ['id' => $c->id, 'label' => $c->razon_social])->values()->toArray();
    }

    private function vehiculosOptions(): array
    {
        return VehiculoEloquentModel::where('activo', true)->orderBy('placa')->get()
            ->map(fn ($v) => ['id' => $v->id, 'label' => $v->placa . ' — ' . $v->marca . ' ' . $v->modelo, 'clienteId' => $v->cliente_id])
            ->values()->toArray();
    }

    private function mecanicosOptions(): array
    {
        return MecanicoEloquentModel::where('activo', true)->orderBy('nombres')->get()
            ->map(fn ($m) => ['id' => $m->id, 'label' => trim($m->nombres . ' ' . $m->apellidos)])->values()->toArray();
    }

    private function serviciosOptions(): array
    {
        return ServicioEloquentModel::where('activo', true)->orderBy('nombre')->get()
            ->map(fn ($s) => ['id' => $s->id, 'label' => $s->nombre, 'precioBase' => (float) $s->precio_base])->values()->toArray();
    }

    private function repuestosOptions(): array
    {
        return ProductoEloquentModel::where('activo', true)->orderBy('nombre')->get()
            ->map(fn ($p) => ['id' => $p->id, 'label' => $p->nombre, 'precio' => (float) $p->precio, 'stock' => $p->stock])->values()->toArray();
    }
}
