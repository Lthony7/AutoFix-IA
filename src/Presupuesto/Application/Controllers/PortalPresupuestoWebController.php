<?php

namespace Src\Presupuesto\Application\Controllers;

use App\Enums\PresupuestoEstado;
use App\Http\Controllers\Controller;
use App\Services\ClienteCuentaService;
use App\Support\InertiaTablePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Presupuesto\Application\Services\PresupuestoLineasService;
use Src\Presupuesto\Infrastructure\Models\PresupuestoEloquentModel;
use Src\Presupuesto\Infrastructure\Requests\StorePresupuestoRequest;
use Src\Presupuesto\Infrastructure\Requests\UpdatePresupuestoRequest;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class PortalPresupuestoWebController extends Controller
{
    public function __construct(
        private readonly PresupuestoLineasService $lineas,
        private readonly ClienteCuentaService $clienteCuenta,
    ) {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $cliente = $this->clienteActual($request);
        if ($redirect = $this->redirigirSinVehiculo($cliente)) {
            return $redirect;
        }

        $paginator = PresupuestoEloquentModel::with(['vehiculo'])
            ->where('cliente_id', $cliente->id)
            ->orderByDesc('created_at')
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn (PresupuestoEloquentModel $p) => $this->lineas->mapPresupuesto($p, false));

        return Inertia::render('Portal/Presupuestos/index', [
            'presupuestos' => InertiaTablePaginator::make($paginator),
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $cliente = $this->clienteActual($request);
        if ($redirect = $this->redirigirSinVehiculo($cliente)) {
            return $redirect;
        }

        return Inertia::render('Portal/Presupuestos/create', [
            'vehiculos' => $this->vehiculosDelCliente($cliente),
            'catalogoServicios' => $this->catalogoServicios(),
            'catalogoRepuestos' => $this->catalogoRepuestos(),
        ]);
    }

    public function store(StorePresupuestoRequest $request): RedirectResponse
    {
        $cliente = $this->clienteActual($request);
        $data = $request->validated();

        if (empty($data['vehiculo_id'])) {
            return redirect()->back()->withInput()->with('error', 'Selecciona un vehículo para el presupuesto.');
        }
        $this->assertVehiculoDelCliente($cliente->id, $data['vehiculo_id']);

        $presupuesto = null;

        DB::transaction(function () use ($cliente, $data, &$presupuesto) {
            $presupuesto = PresupuestoEloquentModel::create([
                'numero' => PresupuestoEloquentModel::generarNumero(),
                'cliente_id' => $cliente->id,
                'vehiculo_id' => $data['vehiculo_id'],
                'estado' => PresupuestoEstado::Guardado,
                'notas' => $data['notas'] ?? null,
                'valido_hasta' => now()->addDays(15)->toDateString(),
                'subtotal_servicios' => 0,
                'subtotal_repuestos' => 0,
                'total' => 0,
            ]);

            $this->lineas->syncLineas(
                $presupuesto,
                $data['servicios'] ?? [],
                $data['repuestos'] ?? [],
            );
        });

        return redirect()
            ->route('portal.presupuestos.show', $presupuesto->id)
            ->with('success', 'Presupuesto guardado. Puedes usarlo al agendar tu cita.');
    }

    public function show(Request $request, string $id): Response
    {
        $presupuesto = $this->findPresupuestoCliente($request, $id);

        return Inertia::render('Portal/Presupuestos/show', [
            'presupuesto' => $this->lineas->mapPresupuesto($presupuesto),
        ]);
    }

    public function edit(Request $request, string $id): Response|RedirectResponse
    {
        $cliente = $this->clienteActual($request);
        if ($redirect = $this->redirigirSinVehiculo($cliente)) {
            return $redirect;
        }

        $presupuesto = $this->findPresupuestoCliente($request, $id);

        if (!$presupuesto->esEditable()) {
            return redirect()
                ->route('portal.presupuestos.show', $presupuesto->id)
                ->with('error', 'Este presupuesto ya no se puede editar.');
        }

        return Inertia::render('Portal/Presupuestos/edit', [
            'presupuesto' => $this->lineas->mapPresupuesto($presupuesto),
            'vehiculos' => $this->vehiculosDelCliente($cliente),
            'catalogoServicios' => $this->catalogoServicios(),
            'catalogoRepuestos' => $this->catalogoRepuestos(),
        ]);
    }

    public function update(UpdatePresupuestoRequest $request, string $id): RedirectResponse
    {
        $presupuesto = $this->findPresupuestoCliente($request, $id);

        if (!$presupuesto->esEditable()) {
            return redirect()->back()->with('error', 'Este presupuesto ya no se puede editar.');
        }

        $cliente = $this->clienteActual($request);
        $data = $request->validated();

        if (empty($data['vehiculo_id'])) {
            return redirect()->back()->withInput()->with('error', 'Selecciona un vehículo para el presupuesto.');
        }
        $this->assertVehiculoDelCliente($cliente->id, $data['vehiculo_id']);

        DB::transaction(function () use ($presupuesto, $data) {
            $presupuesto->update([
                'vehiculo_id' => $data['vehiculo_id'],
                'notas' => $data['notas'] ?? null,
                'estado' => PresupuestoEstado::Guardado,
            ]);

            $this->lineas->syncLineas(
                $presupuesto,
                $data['servicios'] ?? [],
                $data['repuestos'] ?? [],
            );
        });

        return redirect()
            ->route('portal.presupuestos.show', $presupuesto->id)
            ->with('success', 'Presupuesto actualizado.');
    }

    public function cancelar(Request $request, string $id): RedirectResponse
    {
        $presupuesto = $this->findPresupuestoCliente($request, $id);

        if (in_array($presupuesto->estado, [PresupuestoEstado::Cancelado, PresupuestoEstado::VinculadoCita], true)) {
            return redirect()->back()->with('error', 'No se puede cancelar este presupuesto.');
        }

        $presupuesto->update(['estado' => PresupuestoEstado::Cancelado]);

        return redirect()
            ->route('portal.presupuestos.index')
            ->with('success', 'Presupuesto cancelado.');
    }

    private function clienteActual(Request $request): ClienteEloquentModel
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();

        return $this->clienteCuenta->ensureForUser($user);
    }

    private function redirigirSinVehiculo(ClienteEloquentModel $cliente): ?RedirectResponse
    {
        $tieneVehiculo = VehiculoEloquentModel::where('cliente_id', $cliente->id)
            ->where('activo', true)
            ->exists();

        if ($tieneVehiculo) {
            return null;
        }

        return redirect()
            ->route('portal.vehiculos.create', ['return' => 'presupuestos'])
            ->with('warning', 'Primero registra un vehículo para poder armar presupuestos.');
    }

    private function findPresupuestoCliente(Request $request, string $id): PresupuestoEloquentModel
    {
        $cliente = $this->clienteActual($request);

        return PresupuestoEloquentModel::with(['vehiculo', 'cliente', 'servicios', 'repuestos'])
            ->where('cliente_id', $cliente->id)
            ->findOrFail($id);
    }

    private function assertVehiculoDelCliente(string $clienteId, string $vehiculoId): void
    {
        $ok = VehiculoEloquentModel::where('id', $vehiculoId)
            ->where('cliente_id', $clienteId)
            ->exists();

        if (!$ok) {
            abort(403, 'El vehículo no pertenece a tu cuenta.');
        }
    }

    /** @return list<array{id: string, label: string}> */
    private function vehiculosDelCliente(ClienteEloquentModel $cliente): array
    {
        return VehiculoEloquentModel::where('cliente_id', $cliente->id)
            ->where('activo', true)
            ->orderBy('placa')
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'label' => $v->placa . ' — ' . trim(($v->marca ?? '') . ' ' . ($v->modelo ?? '')),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: string, label: string, precio: float}> */
    private function catalogoServicios(): array
    {
        return ServicioEloquentModel::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'label' => $s->nombre,
                'descripcion' => $s->descripcion,
                'precio' => (float) $s->precio_base,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: string, label: string, codigo: string|null, precio: float, stock: int}> */
    private function catalogoRepuestos(): array
    {
        return ProductoEloquentModel::query()
            ->where('tipo_producto', 'repuesto')
            ->where('activo', true)
            ->where('stock', '>', 0)
            ->orderBy('nombre')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'label' => ($p->codigo ? $p->codigo . ' — ' : '') . $p->nombre,
                'codigo' => $p->codigo,
                'precio' => (float) $p->precio,
                'stock' => (int) $p->stock,
            ])
            ->values()
            ->all();
    }
}
