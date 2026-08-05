<?php

namespace Src\DiagnosticoIA\Application\Controllers;

use App\Enums\OrdenEstado;
use App\Enums\SugerenciaIaEstado;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\GenerarDiagnosticoIaService;
use App\Support\InertiaTablePaginator;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\DiagnosticoIA\Infrastructure\Models\DiagnosticoIaEloquentModel;
use Src\DiagnosticoIA\Infrastructure\Requests\RevisarDiagnosticoIaRequest;
use Src\DiagnosticoIA\Infrastructure\Requests\StoreDiagnosticoIaRequest;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;

class DiagnosticoIaWebController extends Controller
{
    public function __construct(
        private readonly GenerarDiagnosticoIaService $generarDiagnostico,
    ) {
    }

    public function index(Request $request): Response
    {
        $query = DiagnosticoIaEloquentModel::with(['ordenTrabajo.cliente', 'ordenTrabajo.vehiculo'])
            ->orderByDesc('created_at');

        if ($request->user()->hasRole(UserRole::Mecanico)) {
            $mecanicoId = $request->user()->mecanico?->id;
            $query->whereHas('ordenTrabajo', fn ($q) => $q->where('mecanico_id', $mecanicoId));
        }

        $paginator = $query
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn (DiagnosticoIaEloquentModel $d) => [
                'id' => $d->id,
                'ordenTrabajoId' => $d->orden_trabajo_id,
                'ordenNumero' => $d->ordenTrabajo?->numero,
                'clienteNombre' => $d->ordenTrabajo?->cliente?->razon_social,
                'vehiculoPlaca' => $d->ordenTrabajo?->vehiculo?->placa,
                'prioridad' => $d->prioridad,
                'servicioRecomendado' => $d->servicio_recomendado,
                'especialidadRecomendada' => $d->especialidad_recomendada,
                'estado' => $d->estado?->value ?? $d->estado,
                'estadoLabel' => $d->estado?->label(),
                'esSimulado' => $d->es_simulado,
                'createdAt' => $d->created_at?->format('Y-m-d H:i:s'),
            ]);

        $countsQuery = DiagnosticoIaEloquentModel::query();
        if ($request->user()->hasRole(UserRole::Mecanico)) {
            $mecanicoId = $request->user()->mecanico?->id;
            $countsQuery->whereHas('ordenTrabajo', fn ($q) => $q->where('mecanico_id', $mecanicoId));
        }

        $counts = (clone $countsQuery)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return Inertia::render('DiagnosticoIA/index', [
            'diagnosticos' => InertiaTablePaginator::make($paginator),
            'stats' => [
                'total' => (int) (clone $countsQuery)->count(),
                'pendientes' => (int) ($counts[SugerenciaIaEstado::Generada->value] ?? 0)
                    + (int) ($counts[SugerenciaIaEstado::EnRevision->value] ?? 0),
                'confirmada' => (int) ($counts[SugerenciaIaEstado::Confirmada->value] ?? 0)
                    + (int) ($counts[SugerenciaIaEstado::Modificada->value] ?? 0),
                'descartada' => (int) ($counts[SugerenciaIaEstado::Descartada->value] ?? 0),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $ordenId = $request->query('ordenTrabajoId');
        $orden = null;

        $ordenesPendientes = OrdenTrabajoEloquentModel::with(['cliente', 'vehiculo'])
            ->whereDoesntHave('sugerenciaIa')
            ->whereIn('estado', [OrdenEstado::Pendiente->value, OrdenEstado::EnDiagnostico->value])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($o) => $this->mapOrdenOption($o))
            ->values()
            ->toArray();

        if ($ordenId) {
            $ordenModel = OrdenTrabajoEloquentModel::with(['cliente', 'vehiculo'])->find($ordenId);
            if ($ordenModel) {
                $orden = $this->mapOrdenOption($ordenModel);
            }
        }

        return Inertia::render('DiagnosticoIA/create', [
            'ordenes' => $ordenesPendientes,
            'orden' => $orden,
        ]);
    }

    public function store(StoreDiagnosticoIaRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $orden = OrdenTrabajoEloquentModel::findOrFail($validated['orden_trabajo_id']);

            $this->generarDiagnostico->generar($orden, $validated);

            return $this->redirectTrasGenerar($request, $orden);
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error al generar diagnóstico: ' . $e->getMessage());
        }
    }

    public function show(Request $request, string $ordenTrabajoId): Response
    {
        $diagnostico = DiagnosticoIaEloquentModel::with(['ordenTrabajo.cliente', 'ordenTrabajo.vehiculo'])
            ->where('orden_trabajo_id', $ordenTrabajoId)
            ->firstOrFail();

        $this->authorizeDiagnosticoView($request, $diagnostico->ordenTrabajo);

        return Inertia::render('DiagnosticoIA/show', [
            'diagnostico' => $this->mapDiagnostico($diagnostico),
        ]);
    }

    public function review(Request $request, string $ordenTrabajoId): Response
    {
        $diagnostico = DiagnosticoIaEloquentModel::with(['ordenTrabajo.cliente', 'ordenTrabajo.vehiculo'])
            ->where('orden_trabajo_id', $ordenTrabajoId)
            ->firstOrFail();

        $this->authorizeDiagnosticoMecanico($request, $diagnostico->ordenTrabajo);

        return Inertia::render('DiagnosticoIA/review', [
            'diagnostico' => $this->mapDiagnostico($diagnostico),
        ]);
    }

    public function revisar(RevisarDiagnosticoIaRequest $request, string $ordenTrabajoId): RedirectResponse
    {
        $diagnostico = DiagnosticoIaEloquentModel::with('ordenTrabajo')
            ->where('orden_trabajo_id', $ordenTrabajoId)
            ->firstOrFail();

        $this->authorizeDiagnosticoMecanico($request, $diagnostico->ordenTrabajo);

        $accion = $request->validated('accion');

        $estado = match ($accion) {
            'confirmar' => SugerenciaIaEstado::Confirmada,
            'modificar' => SugerenciaIaEstado::Modificada,
            'descartar' => SugerenciaIaEstado::Descartada,
        };

        $update = [
            'estado' => $estado,
            'observaciones_revision' => $request->validated('observaciones_revision'),
            'coincide_analisis' => $request->boolean('coincide_analisis'),
        ];

        if ($accion === 'modificar') {
            $update['servicio_recomendado'] = $request->validated('servicio_recomendado') ?? $diagnostico->servicio_recomendado;
            $update['prioridad'] = $request->validated('prioridad') ?? $diagnostico->prioridad;
        }

        $diagnostico->update($update);

        if ($accion === 'confirmar' || $accion === 'modificar') {
            $diagnostico->ordenTrabajo?->update(['estado' => OrdenEstado::EnReparacion]);

            return redirect()
                ->route('ordenes.edit', $ordenTrabajoId)
                ->with('success', 'Diagnóstico confirmado. Continúa la reparación y registra avances para el cliente.');
        }

        return redirect()
            ->route('diagnosticos-ia.show', $ordenTrabajoId)
            ->with('success', 'Revisión registrada exitosamente');
    }

    private function redirectTrasGenerar(Request $request, OrdenTrabajoEloquentModel $orden): RedirectResponse
    {
        $mensaje = 'Diagnóstico IA generado. El mecánico asignado debe revisarlo, contrastarlo con su análisis y añadir observaciones.';

        if ($request->user()->hasRole(UserRole::Mecanico, UserRole::Administrador)) {
            return redirect()
                ->route('diagnosticos-ia.show', $orden->id)
                ->with('success', $mensaje);
        }

        return redirect()
            ->route('ordenes.edit', $orden->id)
            ->with('success', $mensaje);
    }

    private function authorizeDiagnosticoView(Request $request, ?OrdenTrabajoEloquentModel $orden): void
    {
        if (!$orden) {
            abort(404);
        }

        if ($request->user()->hasRole(UserRole::Administrador, UserRole::Recepcionista)) {
            return;
        }

        if (!$request->user()->hasRole(UserRole::Mecanico)) {
            abort(403, 'No autorizado para ver este diagnóstico.');
        }

        $mecanicoId = $request->user()->mecanico?->id;
        if (!$mecanicoId || $orden->mecanico_id !== $mecanicoId) {
            abort(403, 'Solo el mecánico asignado a esta orden puede ver este diagnóstico.');
        }
    }

    private function authorizeDiagnosticoMecanico(Request $request, ?OrdenTrabajoEloquentModel $orden): void
    {
        if (!$orden) {
            abort(404);
        }

        if ($request->user()->hasRole(UserRole::Administrador)) {
            return;
        }

        if (!$request->user()->hasRole(UserRole::Mecanico)) {
            abort(403, 'Solo el mecánico puede ver y confirmar el diagnóstico IA.');
        }

        $mecanicoId = $request->user()->mecanico?->id;
        if (!$mecanicoId || $orden->mecanico_id !== $mecanicoId) {
            abort(403, 'Solo el mecánico asignado a esta orden puede ver y ejecutar este diagnóstico.');
        }
    }

    private function mapDiagnostico(DiagnosticoIaEloquentModel $diagnostico): array
    {
        $input = is_array($diagnostico->input_data) ? $diagnostico->input_data : [];

        return [
            'id' => $diagnostico->id,
            'ordenTrabajoId' => $diagnostico->orden_trabajo_id,
            'inputData' => $input,
            'tipoFalla' => $input['tipo_falla'] ?? null,
            'reporteCliente' => $input['descripcion'] ?? $input['falla_reportada'] ?? null,
            'urgenciaSolicitada' => $input['urgencia'] ?? null,
            'respuestaCompleta' => $diagnostico->respuesta_completa,
            'diagnosticoDetalle' => $diagnostico->diagnostico_detalle,
            'posiblesCausas' => $diagnostico->posibles_causas,
            'accionesRecomendadas' => $diagnostico->acciones_recomendadas ?? [],
            'especialidadRecomendada' => $diagnostico->especialidad_recomendada,
            'mecanicosSugeridos' => $diagnostico->mecanicos_sugeridos ?? [],
            'servicioRecomendado' => $diagnostico->servicio_recomendado,
            'serviciosSugeridos' => $input['servicios_sugeridos'] ?? [],
            'repuestosSugeridos' => $input['repuestos_sugeridos'] ?? [],
            'prioridad' => $diagnostico->prioridad,
            'observacionMecanico' => $diagnostico->observacion_mecanico,
            'advertencia' => $diagnostico->advertencia,
            'estado' => $diagnostico->estado->value,
            'estadoLabel' => $diagnostico->estado->label(),
            'esSimulado' => $diagnostico->es_simulado,
            'observacionesRevision' => $diagnostico->observaciones_revision,
            'coincideAnalisis' => $diagnostico->coincide_analisis,
            'orden' => [
                'numero' => $diagnostico->ordenTrabajo?->numero,
                'clienteNombre' => $diagnostico->ordenTrabajo?->cliente?->razon_social,
                'vehiculoPlaca' => $diagnostico->ordenTrabajo?->vehiculo?->placa,
                'vehiculoMarca' => $diagnostico->ordenTrabajo?->vehiculo?->marca,
                'vehiculoModelo' => $diagnostico->ordenTrabajo?->vehiculo?->modelo,
                'vehiculoAnio' => $diagnostico->ordenTrabajo?->vehiculo?->anio,
                'kilometrajeIngreso' => $diagnostico->ordenTrabajo?->kilometraje_ingreso,
            ],
            'createdAt' => $diagnostico->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function mapOrdenOption(OrdenTrabajoEloquentModel $orden): array
    {
        $vehiculo = $orden->vehiculo;
        $placa = $vehiculo?->placa ?? 'Sin placa';
        $cliente = $orden->cliente?->razon_social ?? 'Cliente';

        return [
            'id' => $orden->id,
            'numero' => $orden->numero,
            'label' => $orden->numero . ' — ' . $placa . ' — ' . $cliente,
            'clienteNombre' => $orden->cliente?->razon_social,
            'vehiculoPlaca' => $vehiculo?->placa,
            'vehiculoMarca' => $vehiculo?->marca,
            'vehiculoModelo' => $vehiculo?->modelo,
            'vehiculoAnio' => $vehiculo?->anio,
            'vehiculoColor' => $vehiculo?->color,
            'vehiculoCombustible' => $vehiculo?->tipo_combustible,
            'kilometrajeIngreso' => $orden->kilometraje_ingreso,
            'kilometrajeVehiculo' => $vehiculo?->kilometraje,
            'tipoFalla' => $orden->tipo_falla,
            'fallaReportada' => $orden->falla_reportada,
            'prioridad' => $orden->prioridad,
            'estado' => $orden->estado instanceof \BackedEnum ? $orden->estado->value : (string) $orden->estado,
            'estadoLabel' => $orden->estado instanceof \BackedEnum ? $orden->estado->label() : (string) $orden->estado,
        ];
    }
}
