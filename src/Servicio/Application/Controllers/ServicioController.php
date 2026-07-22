<?php

namespace Src\Servicio\Application\Controllers;

use App\Http\Controllers\Controller;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;
use Src\Servicio\Infrastructure\Requests\StoreServicioRequest;
use Src\Servicio\Infrastructure\Requests\UpdateServicioRequest;
use Src\Servicio\Infrastructure\Resources\ServicioResource;

class ServicioController extends Controller
{
    public function index()
    {
        $servicios = ServicioEloquentModel::orderBy('nombre')->get();

        return ServicioResource::collection($servicios);
    }

    public function store(StoreServicioRequest $request)
    {
        $servicio = ServicioEloquentModel::create($request->validated());

        return new ServicioResource($servicio);
    }

    public function show(string $id)
    {
        $servicio = ServicioEloquentModel::find($id);

        if (!$servicio) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio no encontrado',
            ], 404);
        }

        return new ServicioResource($servicio);
    }

    public function update(UpdateServicioRequest $request, string $id)
    {
        $servicio = ServicioEloquentModel::find($id);

        if (!$servicio) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio no encontrado',
            ], 404);
        }

        $servicio->update($request->validated());

        return new ServicioResource($servicio);
    }

    public function destroy(string $id)
    {
        $servicio = ServicioEloquentModel::find($id);

        if (!$servicio) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio no encontrado',
            ], 404);
        }

        $servicio->delete();

        return response()->json([
            'success' => true,
            'message' => 'Servicio eliminado exitosamente',
        ], 200);
    }
}
