<?php

namespace Src\OrdenTrabajo\Application\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\OrdenEstadoNotifier;
use App\Services\OrdenRepuestoStockService;
use Illuminate\Support\Facades\DB;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenServicioEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Requests\AsignarMecanicoRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\CambiarEstadoOrdenRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\StoreOrdenTrabajoRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\UpdateOrdenTrabajoRequest;
use Src\OrdenTrabajo\Infrastructure\Resources\OrdenTrabajoResource;

class OrdenTrabajoController extends Controller
{
    public function __construct(
        private readonly OrdenRepuestoStockService $stockService,
        private readonly OrdenEstadoNotifier $estadoNotifier,
    ) {
    }

    public function index()
    {
        $ordenes = OrdenTrabajoEloquentModel::with(['cliente', 'vehiculo', 'mecanico'])
            ->orderByDesc('created_at')
            ->get();

        return OrdenTrabajoResource::collection($ordenes);
    }

    public function store(StoreOrdenTrabajoRequest $request)
    {
        $orden = DB::transaction(function () use ($request) {
            $data = $request->validated();
            unset($data['servicios'], $data['repuestos']);

            $orden = OrdenTrabajoEloquentModel::create(array_merge($data, [
                'numero' => OrdenTrabajoEloquentModel::generarNumero(),
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]));

            $this->syncServicios($orden, $request->input('servicios', []));
            $this->stockService->aplicarNuevos($orden, $request->input('repuestos', []));

            return $orden;
        });

        $orden->load(['cliente', 'vehiculo', 'mecanico', 'ordenServicios.servicio', 'ordenRepuestos.producto']);

        return new OrdenTrabajoResource($orden);
    }

    public function show(string $id)
    {
        $orden = OrdenTrabajoEloquentModel::with([
            'cliente', 'vehiculo', 'mecanico', 'ordenServicios.servicio', 'ordenRepuestos.producto', 'sugerenciaIa', 'pago',
        ])->find($id);

        if (!$orden) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        return new OrdenTrabajoResource($orden);
    }

    public function update(UpdateOrdenTrabajoRequest $request, string $id)
    {
        $orden = OrdenTrabajoEloquentModel::find($id);

        if (!$orden) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        DB::transaction(function () use ($request, $orden) {
            $data = $request->validated();
            $servicios = $data['servicios'] ?? null;
            $repuestos = $data['repuestos'] ?? null;
            unset($data['servicios'], $data['repuestos']);

            if ($request->user()?->hasRole(UserRole::Recepcionista)) {
                unset($data['diagnostico_tecnico']);
            }

            $data['updated_by'] = $request->user()?->id;

            if ($data !== []) {
                $orden->update($data);
            }

            if ($servicios !== null) {
                $orden->ordenServicios()->delete();
                $this->syncServicios($orden, $servicios);
            }

            if ($repuestos !== null) {
                $this->stockService->reemplazar($orden, $repuestos);
            }
        });

        $orden->load(['cliente', 'vehiculo', 'mecanico', 'ordenServicios.servicio', 'ordenRepuestos.producto']);

        return new OrdenTrabajoResource($orden);
    }

    public function destroy(string $id)
    {
        $orden = OrdenTrabajoEloquentModel::with('ordenRepuestos')->find($id);

        if (!$orden) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        DB::transaction(function () use ($orden) {
            $this->stockService->restaurar($orden);
            $orden->delete();
        });

        return response()->json(['success' => true, 'message' => 'Orden eliminada exitosamente'], 200);
    }

    public function asignarMecanico(AsignarMecanicoRequest $request, string $id)
    {
        $orden = OrdenTrabajoEloquentModel::find($id);

        if (!$orden) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        $orden->update([
            'mecanico_id' => $request->validated('mecanico_id'),
            'updated_by' => $request->user()?->id,
        ]);
        $orden->load(['mecanico']);

        return new OrdenTrabajoResource($orden);
    }

    public function cambiarEstado(CambiarEstadoOrdenRequest $request, string $id)
    {
        $orden = OrdenTrabajoEloquentModel::with(['cliente', 'vehiculo'])->find($id);

        if (!$orden) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        $estadoAnterior = $orden->estado instanceof \BackedEnum ? $orden->estado->value : (string) $orden->estado;
        $orden->update([
            'estado' => $request->validated('estado'),
            'updated_by' => $request->user()?->id,
        ]);
        $this->estadoNotifier->notifyIfChanged($orden->fresh(['cliente', 'vehiculo']), $estadoAnterior);

        return new OrdenTrabajoResource($orden);
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
}
