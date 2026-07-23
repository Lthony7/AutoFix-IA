<?php

namespace Src\DiagnosticoIA\Application\Controllers;

use App\Enums\OrdenEstado;
use App\Enums\SugerenciaIaEstado;
use App\Http\Controllers\Controller;
use App\Services\GroqDiagnosticService;
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
        private readonly GroqDiagnosticService $groqService
    ) {
    }

    public function index(): Response
    {
        $paginator = DiagnosticoIaEloquentModel::with(['ordenTrabajo.cliente', 'ordenTrabajo.vehiculo'])
            ->orderByDesc('created_at')
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
                'estado' => $d->estado?->value ?? $d->estado,
                'estadoLabel' => $d->estado?->label(),
                'esSimulado' => $d->es_simulado,
                'createdAt' => $d->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('DiagnosticoIA/index', [
            'diagnosticos' => InertiaTablePaginator::make($paginator),
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
            ->map(fn ($o) => [
                'id' => $o->id,
                'numero' => $o->numero,
                'label' => $o->numero . ' — ' . ($o->vehiculo?->placa ?? ''),
                'clienteNombre' => $o->cliente?->razon_social,
                'vehiculoPlaca' => $o->vehiculo?->placa,
            ])->toArray();

        if ($ordenId) {
            $ordenModel = OrdenTrabajoEloquentModel::with(['cliente', 'vehiculo'])->find($ordenId);
            if ($ordenModel) {
                $orden = [
                    'id' => $ordenModel->id,
                    'numero' => $ordenModel->numero,
                    'clienteNombre' => $ordenModel->cliente?->razon_social,
                    'vehiculoPlaca' => $ordenModel->vehiculo?->placa,
                    'tipoFalla' => $ordenModel->tipo_falla,
                    'fallaReportada' => $ordenModel->falla_reportada,
                ];
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
            $orden = OrdenTrabajoEloquentModel::with(['cliente', 'vehiculo'])->findOrFail($validated['orden_trabajo_id']);

            $inputData = [
                'orden_trabajo_id' => $orden->id,
                'cliente' => $orden->cliente?->razon_social,
                'vehiculo' => $orden->vehiculo?->placa,
                'tipo_falla' => $validated['tipo_falla'],
                'descripcion' => $validated['descripcion'],
                'momento' => $validated['momento'],
                'luces_tablero' => $validated['luces_tablero'] ?? null,
                'ruidos' => $validated['ruidos'] ?? null,
                'puede_circular' => $validated['puede_circular'],
                'urgencia' => $validated['urgencia'],
                'observaciones' => $validated['observaciones'] ?? null,
                'falla_reportada' => $orden->falla_reportada,
            ];

            $resultado = $this->groqService->analyze($inputData);

            DiagnosticoIaEloquentModel::create([
                'orden_trabajo_id' => $orden->id,
                'input_data' => $inputData,
                'respuesta_completa' => $resultado['respuesta_completa'],
                'posibles_causas' => $resultado['posibles_causas'],
                'servicio_recomendado' => $resultado['servicio_recomendado'],
                'prioridad' => $resultado['prioridad'],
                'observacion_mecanico' => $resultado['observacion_mecanico'],
                'advertencia' => $resultado['advertencia'],
                'estado' => SugerenciaIaEstado::Generada,
                'es_simulado' => $resultado['es_simulado'],
            ]);

            $orden->update([
                'estado' => OrdenEstado::EnDiagnostico,
                'tipo_falla' => $validated['tipo_falla'],
                'prioridad' => $resultado['prioridad'],
            ]);

            return redirect()
                ->route('diagnosticos-ia.show', $orden->id)
                ->with('success', 'Diagnóstico IA generado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error al generar diagnóstico: ' . $e->getMessage());
        }
    }

    public function show(string $ordenTrabajoId): Response
    {
        $diagnostico = DiagnosticoIaEloquentModel::with(['ordenTrabajo.cliente', 'ordenTrabajo.vehiculo'])
            ->where('orden_trabajo_id', $ordenTrabajoId)
            ->firstOrFail();

        return Inertia::render('DiagnosticoIA/show', [
            'diagnostico' => $this->mapDiagnostico($diagnostico),
        ]);
    }

    public function review(string $ordenTrabajoId): Response
    {
        $diagnostico = DiagnosticoIaEloquentModel::with(['ordenTrabajo.cliente', 'ordenTrabajo.vehiculo'])
            ->where('orden_trabajo_id', $ordenTrabajoId)
            ->firstOrFail();

        return Inertia::render('DiagnosticoIA/review', [
            'diagnostico' => $this->mapDiagnostico($diagnostico),
        ]);
    }

    public function revisar(RevisarDiagnosticoIaRequest $request, string $ordenTrabajoId): RedirectResponse
    {
        $diagnostico = DiagnosticoIaEloquentModel::where('orden_trabajo_id', $ordenTrabajoId)->firstOrFail();
        $accion = $request->validated('accion');

        $estado = match ($accion) {
            'confirmar' => SugerenciaIaEstado::Confirmada,
            'modificar' => SugerenciaIaEstado::Modificada,
            'descartar' => SugerenciaIaEstado::Descartada,
        };

        $update = [
            'estado' => $estado,
            'observaciones_revision' => $request->validated('observaciones_revision'),
        ];

        if ($accion === 'modificar') {
            $update['servicio_recomendado'] = $request->validated('servicio_recomendado') ?? $diagnostico->servicio_recomendado;
            $update['prioridad'] = $request->validated('prioridad') ?? $diagnostico->prioridad;
        }

        $diagnostico->update($update);

        if ($accion === 'confirmar' || $accion === 'modificar') {
            $diagnostico->ordenTrabajo?->update(['estado' => OrdenEstado::EnReparacion]);
        }

        return redirect()
            ->route('diagnosticos-ia.show', $ordenTrabajoId)
            ->with('success', 'Revisión registrada exitosamente');
    }

    private function mapDiagnostico(DiagnosticoIaEloquentModel $diagnostico): array
    {
        return [
            'id' => $diagnostico->id,
            'ordenTrabajoId' => $diagnostico->orden_trabajo_id,
            'inputData' => $diagnostico->input_data,
            'respuestaCompleta' => $diagnostico->respuesta_completa,
            'posiblesCausas' => $diagnostico->posibles_causas,
            'servicioRecomendado' => $diagnostico->servicio_recomendado,
            'prioridad' => $diagnostico->prioridad,
            'observacionMecanico' => $diagnostico->observacion_mecanico,
            'advertencia' => $diagnostico->advertencia,
            'estado' => $diagnostico->estado->value,
            'estadoLabel' => $diagnostico->estado->label(),
            'esSimulado' => $diagnostico->es_simulado,
            'observacionesRevision' => $diagnostico->observaciones_revision,
            'orden' => [
                'numero' => $diagnostico->ordenTrabajo?->numero,
                'clienteNombre' => $diagnostico->ordenTrabajo?->cliente?->razon_social,
                'vehiculoPlaca' => $diagnostico->ordenTrabajo?->vehiculo?->placa,
            ],
            'createdAt' => $diagnostico->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
