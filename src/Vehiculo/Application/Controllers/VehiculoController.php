<?php

namespace Src\Vehiculo\Application\Controllers;

use App\Http\Controllers\Controller;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;
use Src\Vehiculo\Infrastructure\Requests\StoreVehiculoRequest;
use Src\Vehiculo\Infrastructure\Requests\UpdateVehiculoRequest;
use Src\Vehiculo\Infrastructure\Resources\VehiculoResource;

class VehiculoController extends Controller
{
    public function index()
    {
        $vehiculos = VehiculoEloquentModel::with('cliente')->orderBy('placa')->get();

        return VehiculoResource::collection($vehiculos);
    }

    public function store(StoreVehiculoRequest $request)
    {
        $vehiculo = VehiculoEloquentModel::create($request->validated());
        $vehiculo->load('cliente');

        return new VehiculoResource($vehiculo);
    }

    public function show(string $id)
    {
        $vehiculo = VehiculoEloquentModel::with('cliente')->find($id);

        if (!$vehiculo) {
            return response()->json([
                'success' => false,
                'message' => 'Vehículo no encontrado',
            ], 404);
        }

        return new VehiculoResource($vehiculo);
    }

    public function update(UpdateVehiculoRequest $request, string $id)
    {
        $vehiculo = VehiculoEloquentModel::find($id);

        if (!$vehiculo) {
            return response()->json([
                'success' => false,
                'message' => 'Vehículo no encontrado',
            ], 404);
        }

        $vehiculo->update($request->validated());
        $vehiculo->load('cliente');

        return new VehiculoResource($vehiculo);
    }

    public function destroy(string $id)
    {
        $vehiculo = VehiculoEloquentModel::find($id);

        if (!$vehiculo) {
            return response()->json([
                'success' => false,
                'message' => 'Vehículo no encontrado',
            ], 404);
        }

        $vehiculo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehículo eliminado exitosamente',
        ]);
    }
}
