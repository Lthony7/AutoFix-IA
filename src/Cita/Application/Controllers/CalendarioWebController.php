<?php

namespace Src\Cita\Application\Controllers;

use App\Enums\CitaEstado;
use App\Enums\CitaTipo;
use App\Enums\OrdenEstado;
use App\Enums\PresupuestoEstado;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\CitaNotifier;
use App\Services\DisponibilidadCitasService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\Cita\Infrastructure\Models\CitaEloquentModel;
use Src\Cita\Infrastructure\Requests\AgendarCitaClienteRequest;
use Src\Cita\Infrastructure\Requests\ReagendarCitaRequest;
use Src\Cita\Infrastructure\Requests\StoreCitaRequest;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenRepuestoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenServicioEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Presupuesto\Application\Services\PresupuestoLineasService;
use Src\Presupuesto\Infrastructure\Models\PresupuestoEloquentModel;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class CalendarioWebController extends Controller
{
    public function __construct(
        private readonly CitaNotifier $citaNotifier,
        private readonly DisponibilidadCitasService $disponibilidad,
        private readonly PresupuestoLineasService $presupuestoLineas,
    ) {
    }

    public function index(Request $request): Response
    {
        $vista = $request->query('vista', 'semana') === 'dia' ? 'dia' : 'semana';
        $fechaRef = Carbon::parse($request->query('fecha', now()->toDateString()))->startOfDay();

        if ($vista === 'dia') {
            $desde = $fechaRef->copy()->startOfDay();
            $hasta = $fechaRef->copy()->endOfDay();
        } else {
            $desde = $fechaRef->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $hasta = $fechaRef->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        }

        $user = $request->user();
        $query = CitaEloquentModel::with(['cliente', 'vehiculo', 'mecanico', 'ordenTrabajo', 'presupuesto'])
            ->whereBetween('fecha_hora', [$desde, $hasta])
            ->orderBy('fecha_hora');

        if ($user->hasRole(UserRole::Mecanico)) {
            $query->where('mecanico_id', $user->mecanico?->id);
        } elseif ($user->hasRole(UserRole::Cliente)) {
            $query->whereIn('cliente_id', $this->clienteIdsDelUsuario());
        }

        if ($mecanicoFiltro = $request->query('mecanico_id')) {
            if ($user->hasRole(UserRole::Administrador, UserRole::Recepcionista)) {
                $query->where('mecanico_id', $mecanicoFiltro);
            }
        }

        $eventos = $query->get()->map(fn (CitaEloquentModel $cita) => $this->mapCita($cita, $user))->values()->all();

        $otsDelDia = [];
        if ($user->hasRole(UserRole::Administrador, UserRole::Recepcionista) && $vista === 'dia') {
            $otsDelDia = OrdenTrabajoEloquentModel::with(['cliente', 'vehiculo', 'mecanico'])
                ->whereDate('created_at', $fechaRef->toDateString())
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (OrdenTrabajoEloquentModel $o) => [
                    'id' => $o->id,
                    'numero' => $o->numero,
                    'estado' => $o->estado instanceof \BackedEnum ? $o->estado->value : $o->estado,
                    'estadoLabel' => $o->estado instanceof \BackedEnum ? $o->estado->label() : $o->estado,
                    'clienteNombre' => $o->cliente?->razon_social,
                    'vehiculoPlaca' => $o->vehiculo?->placa,
                    'mecanicoNombre' => $o->mecanico
                        ? trim(($o->mecanico->nombres ?? '') . ' ' . ($o->mecanico->apellidos ?? ''))
                        : null,
                    'createdAt' => $o->created_at?->format('Y-m-d H:i:s'),
                ])->values()->all();
        }

        $slotsHoy = $this->disponibilidad->slotsParaFecha($fechaRef);
        $esStaff = $user->hasRole(UserRole::Administrador, UserRole::Recepcionista);

        return Inertia::render('Calendario/index', [
            'vista' => $vista,
            'fecha' => $fechaRef->toDateString(),
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'eventos' => $eventos,
            'otsDelDia' => $otsDelDia,
            'slots' => $slotsHoy,
            'fechasHabiles' => $this->disponibilidad->fechasHabilesProximas(),
            'agenda' => [
                'horaInicio' => config('autofix.agenda.hora_inicio', '08:00'),
                'horaFin' => config('autofix.agenda.hora_fin', '17:00'),
                'duracionSlot' => (int) config('autofix.agenda.duracion_slot_minutos', 60),
                'maxPorSlot' => (int) config('autofix.agenda.max_citas_por_slot', 3),
            ],
            'puedeCrear' => $esStaff,
            'puedeAgendar' => $user->hasRole(UserRole::Cliente),
            'puedeCrearOt' => $esStaff,
            'esCliente' => $user->hasRole(UserRole::Cliente),
            'esMecanico' => $user->hasRole(UserRole::Mecanico),
            'antelacionHoras' => (int) config('autofix.agenda.antelacion_minima_horas', 12),
            'clientes' => $esStaff ? $this->clientesOptions() : [],
            'vehiculos' => $esStaff
                ? $this->vehiculosOptions()
                : ($user->hasRole(UserRole::Cliente) ? $this->vehiculosDelCliente() : []),
            'mecanicos' => $user->hasRole(UserRole::Administrador, UserRole::Recepcionista, UserRole::Mecanico)
                ? $this->mecanicosOptions()
                : [],
            'tipos' => collect(CitaTipo::cases())->map(fn (CitaTipo $t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ])->values()->all(),
            'presupuestosDisponibles' => $user->hasRole(UserRole::Cliente)
                ? $this->presupuestosDisponiblesCliente()
                : [],
            'catalogoServicios' => $user->hasRole(UserRole::Cliente)
                ? $this->catalogoServiciosCliente()
                : [],
            'catalogoRepuestos' => $user->hasRole(UserRole::Cliente)
                ? $this->catalogoRepuestosCliente()
                : [],
            'presupuestoPreseleccionado' => $request->query('presupuesto_id', ''),
            'filters' => [
                'mecanico_id' => $request->query('mecanico_id', ''),
            ],
        ]);
    }

    public function disponibilidad(Request $request): JsonResponse
    {
        $fecha = (string) $request->query('fecha', now()->toDateString());
        $excluir = $request->query('excluir_cita_id');

        return response()->json([
            'fecha' => $fecha,
            'slots' => $this->disponibilidad->slotsParaFecha($fecha, $excluir ? (string) $excluir : null),
        ]);
    }

    public function store(StoreCitaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (!$this->disponibilidad->esSlotDisponible((string) $data['fecha_hora'])) {
            return redirect()->back()->withInput()
                ->with('error', 'Ese horario no tiene cupo disponible.');
        }

        CitaEloquentModel::create([
            ...$data,
            'duracion_minutos' => $data['duracion_minutos']
                ?? (int) config('autofix.agenda.duracion_slot_minutos', 60),
            'estado' => CitaEstado::Programada,
        ]);

        return redirect()
            ->route('calendario.index', [
                'fecha' => Carbon::parse($data['fecha_hora'])->toDateString(),
                'vista' => 'dia',
            ])
            ->with('success', 'Cita programada exitosamente');
    }

    public function agendar(AgendarCitaClienteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $cliente = ClienteEloquentModel::where('user_id', $request->user()?->id)->firstOrFail();
        $vehiculo = VehiculoEloquentModel::findOrFail($data['vehiculo_id']);
        $presupuestoId = $data['presupuesto_id'] ?? null;

        $cita = null;

        DB::transaction(function () use ($data, $cliente, $vehiculo, $presupuestoId, &$cita) {
            if ($presupuestoId) {
                $presupuesto = PresupuestoEloquentModel::where('cliente_id', $cliente->id)
                    ->findOrFail($presupuestoId);

                if (!empty($data['ajustar_presupuesto'])) {
                    $this->presupuestoLineas->syncLineas(
                        $presupuesto,
                        $data['servicios'] ?? [],
                        $data['repuestos'] ?? [],
                    );
                    if ($presupuesto->vehiculo_id !== $vehiculo->id) {
                        $presupuesto->update(['vehiculo_id' => $vehiculo->id]);
                    }
                }

                $presupuesto->update(['estado' => PresupuestoEstado::VinculadoCita]);
            }

            $cita = CitaEloquentModel::create([
                'cliente_id' => $cliente->id,
                'vehiculo_id' => $vehiculo->id,
                'presupuesto_id' => $presupuestoId,
                'fecha_hora' => $data['fecha_hora'],
                'duracion_minutos' => (int) config('autofix.agenda.duracion_slot_minutos', 60),
                'tipo' => $data['tipo'],
                'estado' => CitaEstado::Programada,
                'notas' => $data['notas'] ?? null,
            ]);
        });

        $this->citaNotifier->notifyTaller($cita->load(['cliente', 'vehiculo']), 'agendada');

        return redirect()
            ->route('calendario.index', [
                'fecha' => Carbon::parse($data['fecha_hora'])->toDateString(),
                'vista' => 'dia',
            ])
            ->with('success', 'Cita agendada. El taller la verá en la planificación del día.');
    }

    public function crearOt(Request $request, string $id): RedirectResponse
    {
        $cita = CitaEloquentModel::with(['ordenTrabajo', 'vehiculo', 'presupuesto.servicios', 'presupuesto.repuestos'])
            ->findOrFail($id);

        if ($cita->orden_trabajo_id && $cita->ordenTrabajo) {
            return redirect()
                ->route('ordenes.edit', $cita->orden_trabajo_id)
                ->with('success', 'Esta cita ya tiene una orden vinculada.');
        }

        if (!in_array($cita->estado?->value ?? (string) $cita->estado, [
            CitaEstado::Programada->value,
            CitaEstado::Reagendada->value,
        ], true)) {
            return redirect()->back()->with('error', 'Solo se puede crear OT desde citas programadas.');
        }

        $ordenId = null;

        DB::transaction(function () use ($request, $cita, &$ordenId) {
            $tipoLabel = $cita->tipo?->label() ?? 'Atención';
            $falla = $cita->notas
                ?: "Atención agendada ({$tipoLabel}) — " . ($cita->fecha_hora?->format('d/m/Y H:i') ?? '');

            if ($cita->presupuesto) {
                $nombres = $cita->presupuesto->servicios->pluck('nombre')->filter()->values()->all();
                if ($nombres) {
                    $falla = 'Según presupuesto ' . $cita->presupuesto->numero . ': ' . implode(', ', $nombres);
                    if ($cita->notas) {
                        $falla .= "\n" . $cita->notas;
                    }
                }
            }

            $orden = OrdenTrabajoEloquentModel::create([
                'numero' => OrdenTrabajoEloquentModel::generarNumero(),
                'cliente_id' => $cita->cliente_id,
                'vehiculo_id' => $cita->vehiculo_id,
                'mecanico_id' => $cita->mecanico_id,
                'estado' => OrdenEstado::Pendiente,
                'tipo_falla' => $cita->tipo?->value === CitaTipo::Mantenimiento->value
                    ? 'Mantenimiento'
                    : ($cita->tipo?->label() ?? null),
                'falla_reportada' => $falla,
                'kilometraje_ingreso' => (int) ($cita->vehiculo?->kilometraje ?? 0),
                'observaciones' => $cita->presupuesto
                    ? 'Generada desde cita con presupuesto ' . $cita->presupuesto->numero . '.'
                    : 'Generada desde cita del calendario.',
                'prioridad' => 'media',
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            if ($cita->presupuesto) {
                foreach ($cita->presupuesto->servicios as $linea) {
                    $cantidad = max(1, (int) $linea->cantidad);
                    OrdenServicioEloquentModel::create([
                        'orden_trabajo_id' => $orden->id,
                        'servicio_id' => $linea->servicio_id,
                        'precio' => round((float) $linea->precio * $cantidad, 2),
                    ]);
                }

                foreach ($cita->presupuesto->repuestos as $linea) {
                    OrdenRepuestoEloquentModel::create([
                        'orden_trabajo_id' => $orden->id,
                        'producto_id' => $linea->producto_id,
                        'cantidad' => max(1, (int) $linea->cantidad),
                        'precio_unitario' => $linea->precio_unitario,
                    ]);
                }
            }

            $cita->update(['orden_trabajo_id' => $orden->id]);
            $ordenId = $orden->id;
        });

        return redirect()
            ->route('diagnosticos-ia.create', ['ordenTrabajoId' => $ordenId])
            ->with('success', 'OT creada desde la cita. Continúa con el diagnóstico IA.');
    }

    public function cancelar(Request $request, string $id): RedirectResponse
    {
        $cita = $this->findCitaForUser($request, $id);

        if (!$this->puedeModificarCliente($cita)) {
            return redirect()->back()->with('error', $this->mensajeBloqueoModificacion($cita));
        }

        $cita->update(['estado' => CitaEstado::Cancelada]);
        $this->citaNotifier->notifyTaller($cita->fresh(['cliente', 'vehiculo']), 'cancelada');

        return redirect()
            ->route('calendario.index')
            ->with('success', 'Cita cancelada. El taller ha sido notificado.');
    }

    public function reagendar(ReagendarCitaRequest $request, string $id): RedirectResponse
    {
        $cita = $this->findCitaForUser($request, $id);

        if (!$this->puedeModificarCliente($cita)) {
            return redirect()->back()->with('error', $this->mensajeBloqueoModificacion($cita));
        }

        $data = $request->validated();

        if (!$this->disponibilidad->esSlotDisponible((string) $data['fecha_hora'], $cita->id)) {
            return redirect()->back()->withInput()
                ->with('error', 'Ese horario no tiene cupo disponible.');
        }

        $cita->update([
            'fecha_hora' => $data['fecha_hora'],
            'estado' => CitaEstado::Programada,
            'notas' => $data['notas'] ?? $cita->notas,
        ]);

        $this->citaNotifier->notifyTaller($cita->fresh(['cliente', 'vehiculo']), 'reagendada');

        return redirect()
            ->route('calendario.index', [
                'fecha' => Carbon::parse($data['fecha_hora'])->toDateString(),
                'vista' => 'dia',
            ])
            ->with('success', 'Cita reagendada. El taller ha sido notificado.');
    }

    public function completar(Request $request, string $id): RedirectResponse
    {
        $cita = CitaEloquentModel::findOrFail($id);
        $user = $request->user();

        if ($user->hasRole(UserRole::Mecanico)) {
            if ($cita->mecanico_id !== $user->mecanico?->id) {
                abort(403, 'Solo puedes completar tus propias citas.');
            }
        } elseif (!$user->hasRole(UserRole::Administrador, UserRole::Recepcionista)) {
            abort(403);
        }

        $cita->update(['estado' => CitaEstado::Completada]);

        return redirect()->back()->with('success', 'Cita marcada como completada');
    }

    private function findCitaForUser(Request $request, string $id): CitaEloquentModel
    {
        $cita = CitaEloquentModel::with(['ordenTrabajo', 'cliente', 'vehiculo'])->findOrFail($id);
        $user = $request->user();

        if ($user->hasRole(UserRole::Cliente)) {
            if (!in_array($cita->cliente_id, $this->clienteIdsDelUsuario(), true)) {
                abort(403, 'No tienes acceso a esta cita.');
            }
        } elseif ($user->hasRole(UserRole::Mecanico)) {
            if ($cita->mecanico_id !== $user->mecanico?->id) {
                abort(403, 'No tienes acceso a esta cita.');
            }
        } elseif (!$user->hasRole(UserRole::Administrador, UserRole::Recepcionista)) {
            abort(403);
        }

        return $cita;
    }

    private function puedeModificarCliente(CitaEloquentModel $cita): bool
    {
        $antelacion = (int) config('autofix.agenda.antelacion_minima_horas', 12);

        if (!in_array($cita->estado?->value ?? (string) $cita->estado, [
            CitaEstado::Programada->value,
            CitaEstado::Reagendada->value,
        ], true)) {
            return false;
        }

        $orden = $cita->ordenTrabajo;
        if ($orden) {
            $estadoOt = $orden->estado instanceof \BackedEnum ? $orden->estado->value : (string) $orden->estado;
            if (in_array($estadoOt, [
                OrdenEstado::EnReparacion->value,
                OrdenEstado::Finalizada->value,
                OrdenEstado::Entregada->value,
            ], true)) {
                return false;
            }
        }

        if ($cita->fecha_hora && $cita->fecha_hora->lte(now()->addHours($antelacion))) {
            return false;
        }

        return true;
    }

    private function mensajeBloqueoModificacion(CitaEloquentModel $cita): string
    {
        $antelacion = (int) config('autofix.agenda.antelacion_minima_horas', 12);
        $orden = $cita->ordenTrabajo;

        if ($orden) {
            $estadoOt = $orden->estado instanceof \BackedEnum ? $orden->estado->value : (string) $orden->estado;
            if (in_array($estadoOt, [
                OrdenEstado::EnReparacion->value,
                OrdenEstado::Finalizada->value,
                OrdenEstado::Entregada->value,
            ], true)) {
                return 'No puedes cancelar ni reagendar: el taller ya está trabajando o finalizó la orden. Contacta recepción.';
            }
        }

        if ($cita->fecha_hora && $cita->fecha_hora->lte(now()->addHours($antelacion))) {
            return "Solo puedes cancelar o reagendar con al menos {$antelacion} horas de antelación. Contacta recepción.";
        }

        return 'Esta cita ya no se puede modificar.';
    }

    /** @return array<string, mixed> */
    private function mapCita(CitaEloquentModel $cita, $user): array
    {
        $puedeModificar = $user->hasRole(UserRole::Cliente) && $this->puedeModificarCliente($cita);
        $estadoCita = $cita->estado?->value ?? (string) $cita->estado;
        $puedeCrearOt = $user->hasRole(UserRole::Administrador, UserRole::Recepcionista)
            && !$cita->orden_trabajo_id
            && in_array($estadoCita, [CitaEstado::Programada->value, CitaEstado::Reagendada->value], true);

        return [
            'id' => $cita->id,
            'fechaHora' => $cita->fecha_hora?->format('Y-m-d H:i:s'),
            'fecha' => $cita->fecha_hora?->toDateString(),
            'hora' => $cita->fecha_hora?->format('H:i'),
            'duracionMinutos' => $cita->duracion_minutos,
            'tipo' => $cita->tipo?->value ?? $cita->tipo,
            'tipoLabel' => $cita->tipo?->label(),
            'estado' => $estadoCita,
            'estadoLabel' => $cita->estado?->label(),
            'notas' => $cita->notas,
            'clienteNombre' => $cita->cliente?->razon_social,
            'vehiculoPlaca' => $cita->vehiculo?->placa,
            'mecanicoNombre' => $cita->mecanico
                ? trim(($cita->mecanico->nombres ?? '') . ' ' . ($cita->mecanico->apellidos ?? ''))
                : null,
            'ordenTrabajoId' => $cita->orden_trabajo_id,
            'ordenNumero' => $cita->ordenTrabajo?->numero,
            'presupuestoId' => $cita->presupuesto_id,
            'presupuestoNumero' => $cita->presupuesto?->numero,
            'presupuestoTotal' => $cita->presupuesto ? (float) $cita->presupuesto->total : null,
            'puedeCancelar' => $puedeModificar,
            'puedeReagendar' => $puedeModificar,
            'puedeCrearOt' => $puedeCrearOt,
            'puedeCompletar' => $user->hasRole(UserRole::Mecanico, UserRole::Administrador, UserRole::Recepcionista)
                && in_array($estadoCita, [
                    CitaEstado::Programada->value,
                    CitaEstado::Reagendada->value,
                ], true),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function presupuestosDisponiblesCliente(): array
    {
        return PresupuestoEloquentModel::with(['servicios', 'repuestos', 'vehiculo'])
            ->whereIn('cliente_id', $this->clienteIdsDelUsuario())
            ->whereIn('estado', [
                PresupuestoEstado::Guardado->value,
                PresupuestoEstado::Borrador->value,
            ])
            ->where(function ($q) {
                $q->whereNull('valido_hasta')
                    ->orWhereDate('valido_hasta', '>=', now()->toDateString());
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PresupuestoEloquentModel $p) => $this->presupuestoLineas->mapPresupuesto($p))
            ->values()
            ->all();
    }

    /** @return list<array{id: string, label: string, precio: float}> */
    private function catalogoServiciosCliente(): array
    {
        return ServicioEloquentModel::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'label' => $s->nombre,
                'precio' => (float) $s->precio_base,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: string, label: string, precio: float, stock: int}> */
    private function catalogoRepuestosCliente(): array
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
                'precio' => (float) $p->precio,
                'stock' => (int) $p->stock,
            ])
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function clienteIdsDelUsuario(): array
    {
        return ClienteEloquentModel::where('user_id', auth()->id())->pluck('id')->all();
    }

    /** @return list<array{id: string, label: string}> */
    private function clientesOptions(): array
    {
        return ClienteEloquentModel::where('estado', true)
            ->orderBy('razon_social')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'label' => $c->razon_social])
            ->values()
            ->all();
    }

    /** @return list<array{id: string, label: string, clienteId: string}> */
    private function vehiculosOptions(): array
    {
        return VehiculoEloquentModel::where('activo', true)
            ->orderBy('placa')
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'label' => $v->placa . ' — ' . trim(($v->marca ?? '') . ' ' . ($v->modelo ?? '')),
                'clienteId' => $v->cliente_id,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: string, label: string, clienteId: string}> */
    private function vehiculosDelCliente(): array
    {
        return VehiculoEloquentModel::where('activo', true)
            ->whereIn('cliente_id', $this->clienteIdsDelUsuario())
            ->orderBy('placa')
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'label' => $v->placa . ' — ' . trim(($v->marca ?? '') . ' ' . ($v->modelo ?? '')),
                'clienteId' => $v->cliente_id,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: string, label: string}> */
    private function mecanicosOptions(): array
    {
        return MecanicoEloquentModel::where('activo', true)
            ->orderBy('nombres')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'label' => trim(($m->nombres ?? '') . ' ' . ($m->apellidos ?? '')),
            ])
            ->values()
            ->all();
    }
}
