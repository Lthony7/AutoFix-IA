<?php

namespace Src\OrdenTrabajo\Application\Controllers;

use App\Enums\UserRole;
use App\Enums\CitaEstado;
use App\Enums\CitaTipo;
use App\Http\Controllers\Controller;
use App\Services\OrdenEstadoNotifier;
use App\Services\OrdenRepuestoStockService;
use App\Support\InertiaTablePaginator;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\Cita\Infrastructure\Models\CitaEloquentModel;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenAvanceEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenServicioEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Requests\AsignarMecanicoRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\CambiarEstadoOrdenRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\StoreOrdenAvanceRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\StoreOrdenTrabajoRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\UpdateOrdenTrabajoRequest;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class OrdenTrabajoWebController extends Controller
{
    public function __construct(
        private readonly OrdenRepuestoStockService $stockService,
        private readonly OrdenEstadoNotifier $estadoNotifier,
    ) {
    }
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
            'creator',
            'updater',
        ])->orderByDesc('created_at');

        if ($user->hasRole(UserRole::Mecanico)) {
            $mecanicoId = $user->mecanico?->id;
            $query->where('mecanico_id', $mecanicoId);
        }

        $paginator = $query
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn ($orden) => $this->mapOrden($orden));

        return Inertia::render('OrdenTrabajo/index', [
            'ordenes' => InertiaTablePaginator::make($paginator),
            'mecanicos' => $this->mecanicosOptions(),
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
            $ordenId = null;

            DB::transaction(function () use ($request, &$ordenId) {
                $data = $request->validated();
                $fechaCita = $data['fecha_cita'] ?? null;
                $tipoCita = $data['tipo_cita'] ?? CitaTipo::Reparacion->value;
                unset($data['servicios'], $data['repuestos'], $data['fecha_cita'], $data['tipo_cita']);

                $orden = OrdenTrabajoEloquentModel::create(array_merge($data, [
                    'numero' => OrdenTrabajoEloquentModel::generarNumero(),
                    'created_by' => $request->user()?->id,
                    'updated_by' => $request->user()?->id,
                ]));

                if ($fechaCita) {
                    CitaEloquentModel::create([
                        'cliente_id' => $orden->cliente_id,
                        'vehiculo_id' => $orden->vehiculo_id,
                        'mecanico_id' => $orden->mecanico_id,
                        'orden_trabajo_id' => $orden->id,
                        'fecha_hora' => $fechaCita,
                        'duracion_minutos' => 60,
                        'tipo' => CitaTipo::tryFrom($tipoCita) ?? CitaTipo::Reparacion,
                        'estado' => CitaEstado::Programada,
                        'notas' => 'Cita vinculada a ' . $orden->numero,
                    ]);
                }

                $ordenId = $orden->id;
            });

            return redirect()
                ->route('diagnosticos-ia.create', ['ordenTrabajoId' => $ordenId])
                ->with('success', 'Orden creada. Ahora genera el diagnóstico IA para asignar especialista, servicios y repuestos.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error al crear la orden: ' . $e->getMessage());
        }
    }

    public function edit(Request $request, string $id): Response
    {
        $orden = $this->findOrdenForUser($request, $id);
        $orden->load([
            'cliente',
            'vehiculo',
            'mecanico',
            'ordenServicios.servicio',
            'ordenRepuestos.producto',
            'avances.user',
            'creator',
            'updater',
            'sugerenciaIa',
            'factura',
        ]);

        $sugerencia = $orden->sugerenciaIa;

        return Inertia::render('OrdenTrabajo/edit', [
            'orden' => $this->mapOrden($orden, true),
            'sugerenciaIa' => $sugerencia ? [
                'id' => $sugerencia->id,
                'estado' => $sugerencia->estado instanceof \BackedEnum ? $sugerencia->estado->value : $sugerencia->estado,
                'estadoLabel' => $sugerencia->estado instanceof \BackedEnum ? $sugerencia->estado->label() : $sugerencia->estado,
                'diagnosticoDetalle' => $sugerencia->diagnostico_detalle,
                'servicioRecomendado' => $sugerencia->servicio_recomendado,
                'especialidadRecomendada' => $sugerencia->especialidad_recomendada,
                'prioridad' => $sugerencia->prioridad,
                'mecanicosSugeridos' => $sugerencia->mecanicos_sugeridos ?? [],
                'serviciosSugeridos' => $sugerencia->input_data['servicios_sugeridos'] ?? [],
                'repuestosSugeridos' => $sugerencia->input_data['repuestos_sugeridos'] ?? [],
                'esSimulado' => (bool) $sugerencia->es_simulado,
            ] : null,
            'clientes' => $this->clientesOptions(),
            'vehiculos' => $this->vehiculosOptions(),
            'mecanicos' => $this->mecanicosOptions(),
            'servicios' => $this->serviciosOptions(),
            'repuestos' => $this->repuestosOptions(),
            'soloDiagnostico' => false,
            'puedeEditarDiagnostico' => $request->user()->hasRole(UserRole::Administrador)
                || $request->user()->hasRole(UserRole::Mecanico),
            'puedeRegistrarAvance' => $request->user()->hasRole(UserRole::Administrador)
                || $request->user()->hasRole(UserRole::Mecanico),
            'puedeGestionarServicios' => $request->user()->hasRole(UserRole::Mecanico)
                || $request->user()->hasRole(UserRole::Administrador),
            'puedeGestionarRepuestos' => $request->user()->hasRole(UserRole::Mecanico)
                || $request->user()->hasRole(UserRole::Administrador),
            'puedeCorregirItems' => $request->user()->hasRole(UserRole::Administrador)
                || $request->user()->hasRole(UserRole::Recepcionista)
                || $request->user()->hasRole(UserRole::Mecanico),
            'esMecanico' => $request->user()->hasRole(UserRole::Mecanico),
        ]);
    }

    public function storeAvance(StoreOrdenAvanceRequest $request, string $id): RedirectResponse
    {
        $orden = $this->findOrdenForUser($request, $id);

        OrdenAvanceEloquentModel::create([
            'orden_trabajo_id' => $orden->id,
            'user_id' => $request->user()->id,
            'mensaje' => $request->validated('mensaje'),
        ]);

        $orden->update(['updated_by' => $request->user()->id]);

        return redirect()
            ->route('ordenes.edit', $orden->id)
            ->with('success', 'Avance registrado en la bitácora');
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

                if ($request->user()->hasRole(UserRole::Recepcionista)) {
                    unset($data['diagnostico_tecnico']);
                }

                $data['updated_by'] = $request->user()?->id;

                $esMecanico = $request->user()->hasRole(UserRole::Mecanico);

                if ($esMecanico) {
                    $orden->update(array_filter([
                        'diagnostico_tecnico' => $data['diagnostico_tecnico'] ?? null,
                        'observaciones' => $data['observaciones'] ?? null,
                        'mecanico_id' => $data['mecanico_id'] ?? $orden->mecanico_id,
                        'updated_by' => $data['updated_by'],
                    ], fn ($v) => $v !== null));
                } elseif ($data !== []) {
                    $orden->update($data);
                }

                if (array_key_exists('mecanico_id', $data) || $esMecanico) {
                    $orden->load('cita');
                    if ($orden->cita) {
                        $orden->cita->update(['mecanico_id' => $orden->fresh()->mecanico_id]);
                    }
                }

                if ($servicios !== null && (
                    $request->user()->hasRole(UserRole::Mecanico)
                    || $request->user()->hasRole(UserRole::Administrador)
                )) {
                    $orden->ordenServicios()->delete();
                    $this->syncServicios($orden, $servicios);
                }

                if ($repuestos !== null && (
                    $request->user()->hasRole(UserRole::Mecanico)
                    || $request->user()->hasRole(UserRole::Administrador)
                )) {
                    $this->stockService->reemplazar($orden, $repuestos);
                }
            });

            return redirect()->route('ordenes.edit', $orden->id)->with('success', 'Orden actualizada. Si ya está lista, genera la factura.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error al actualizar la orden: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $orden = OrdenTrabajoEloquentModel::with('ordenRepuestos')->find($id);

        if (!$orden) {
            return redirect()->back()->with('error', 'Orden no encontrada');
        }

        if ($request->user()->hasRole(UserRole::Mecanico)) {
            abort(403, 'No tienes permiso para eliminar órdenes.');
        }

        DB::transaction(function () use ($orden) {
            $this->stockService->restaurar($orden);
            $orden->delete();
        });

        return redirect()->route('ordenes.index')->with('success', 'Orden eliminada exitosamente');
    }

    public function asignarMecanico(AsignarMecanicoRequest $request, string $id): RedirectResponse
    {
        $orden = OrdenTrabajoEloquentModel::with('cita')->findOrFail($id);
        $orden->update([
            'mecanico_id' => $request->validated('mecanico_id'),
            'updated_by' => $request->user()?->id,
        ]);

        if ($orden->cita) {
            $orden->cita->update(['mecanico_id' => $request->validated('mecanico_id')]);
        }

        return redirect()->back()->with('success', 'Mecánico asignado exitosamente');
    }

    public function cambiarEstado(CambiarEstadoOrdenRequest $request, string $id): RedirectResponse
    {
        $orden = $this->findOrdenForUser($request, $id);
        $orden->load(['cliente', 'vehiculo']);
        $estadoAnterior = $orden->estado instanceof \BackedEnum ? $orden->estado->value : (string) $orden->estado;
        $orden->update([
            'estado' => $request->validated('estado'),
            'updated_by' => $request->user()?->id,
        ]);
        $this->estadoNotifier->notifyIfChanged($orden->fresh(['cliente', 'vehiculo']), $estadoAnterior);

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
            'createdByNombre' => $orden->creator?->name,
            'updatedByNombre' => $orden->updater?->name,
            'createdAt' => $orden->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $orden->updated_at?->format('Y-m-d H:i:s'),
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

            $data['avances'] = $orden->avances->map(fn ($avance) => [
                'id' => $avance->id,
                'mensaje' => $avance->mensaje,
                'usuarioNombre' => $avance->user?->name ?? 'Usuario',
                'createdAt' => $avance->created_at?->format('Y-m-d H:i:s'),
            ])->values()->toArray();
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

    private function clientesOptions(): array
    {
        return ClienteEloquentModel::where('estado', true)->orderBy('razon_social')->get()
            ->map(fn ($c) => ['id' => $c->id, 'label' => $c->razon_social])->values()->toArray();
    }

    private function vehiculosOptions(): array
    {
        return VehiculoEloquentModel::where('activo', true)->orderBy('placa')->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'label' => $v->placa . ' — ' . $v->marca . ' ' . $v->modelo,
                'clienteId' => $v->cliente_id,
                'placa' => $v->placa,
                'marca' => $v->marca,
                'modelo' => $v->modelo,
                'anio' => $v->anio,
                'color' => $v->color,
                'kilometraje' => (int) $v->kilometraje,
                'tipoCombustible' => $v->tipo_combustible,
            ])
            ->values()->toArray();
    }

    private function mecanicosOptions(): array
    {
        return MecanicoEloquentModel::where('activo', true)->orderBy('nombres')->get()
            ->map(function ($m) {
                $nombre = trim($m->nombres . ' ' . $m->apellidos);

                return [
                    'id' => $m->id,
                    'label' => $nombre . ' — ' . $m->especialidad,
                    'nombreCompleto' => $nombre,
                    'documento' => $m->documento,
                    'telefono' => $m->telefono,
                    'email' => $m->email,
                    'especialidad' => $m->especialidad,
                    'horarioDisponible' => $m->horario_disponible,
                    'activo' => (bool) $m->activo,
                ];
            })
            ->values()
            ->toArray();
    }

    private function serviciosOptions(): array
    {
        $servicios = ServicioEloquentModel::where('activo', true)->get();

        return $servicios
            ->sortBy(function ($s) {
                $nombre = mb_strtolower((string) $s->nombre);
                // Diagnóstico computarizado siempre primero en el listado
                if (str_contains($nombre, 'diagnóstico computarizado') || str_contains($nombre, 'diagnostico computarizado')) {
                    return '0_' . $nombre;
                }

                return '1_' . $nombre;
            })
            ->values()
            ->map(fn ($s) => [
                'id' => $s->id,
                'label' => $s->nombre,
                'precioBase' => (float) $s->precio_base,
                'descripcion' => $s->descripcion,
            ])
            ->all();
    }

    private function repuestosOptions(): array
    {
        return ProductoEloquentModel::where('activo', true)
            ->where('tipo_producto', 'repuesto')
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'label' => trim(($p->categoria ? "[{$p->categoria}] " : '') . $p->nombre . " (stock: {$p->stock})"),
                'precio' => (float) $p->precio,
                'stock' => $p->stock,
                'categoria' => $p->categoria,
            ])
            ->values()
            ->toArray();
    }
}
