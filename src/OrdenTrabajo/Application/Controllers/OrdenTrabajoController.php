<?php

namespace Src\OrdenTrabajo\Application\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenRepuestoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenServicioEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Requests\AsignarMecanicoRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\CambiarEstadoOrdenRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\StoreOrdenTrabajoRequest;
use Src\OrdenTrabajo\Infrastructure\Requests\UpdateOrdenTrabajoRequest;
use Src\OrdenTrabajo\Infrastructure\Resources\OrdenTrabajoResource;

class OrdenTrabajoController extends Controller
{
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
            ]));

            $this->syncServicios($orden, $request->input('servicios', []));
            $this->syncRepuestos($orden, $request->input('repuestos', []));

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
        });

        $orden->load(['cliente', 'vehiculo', 'mecanico', 'ordenServicios.servicio', 'ordenRepuestos.producto']);

        return new OrdenTrabajoResource($orden);
    }

    public function destroy(string $id)
    {
        $orden = OrdenTrabajoEloquentModel::find($id);

        if (!$orden) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        $orden->delete();

        return response()->json(['success' => true, 'message' => 'Orden eliminada exitosamente'], 200);
    }

    public function asignarMecanico(AsignarMecanicoRequest $request, string $id)
    {
        $orden = OrdenTrabajoEloquentModel::find($id);

        if (!$orden) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        $orden->update(['mecanico_id' => $request->validated('mecanico_id')]);
        $orden->load(['mecanico']);

        return new OrdenTrabajoResource($orden);
    }

    public function cambiarEstado(CambiarEstadoOrdenRequest $request, string $id)
    {
        $orden = OrdenTrabajoEloquentModel::find($id);

        if (!$orden) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        $orden->update(['estado' => $request->validated('estado')]);

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
}
