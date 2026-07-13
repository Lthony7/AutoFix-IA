<?php

namespace Src\Mecanico\Application\Controllers;

use App\Http\Controllers\Controller;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;
use Src\Mecanico\Infrastructure\Requests\StoreMecanicoRequest;
use Src\Mecanico\Infrastructure\Requests\UpdateMecanicoRequest;
use Src\Mecanico\Infrastructure\Resources\MecanicoResource;

class MecanicoController extends Controller
{
    public function index()
    {
        return MecanicoResource::collection(
            MecanicoEloquentModel::orderBy('nombres')->get()
        );
    }

    public function store(StoreMecanicoRequest $request)
    {
        $mecanico = MecanicoEloquentModel::create($request->validated());

        return new MecanicoResource($mecanico);
    }

    public function show(string $id)
    {
        $mecanico = MecanicoEloquentModel::find($id);

        if (!$mecanico) {
            return response()->json([
                'success' => false,
                'message' => 'Mecánico no encontrado',
            ], 404);
        }

        return new MecanicoResource($mecanico);
    }

    public function update(UpdateMecanicoRequest $request, string $id)
    {
        $mecanico = MecanicoEloquentModel::find($id);

        if (!$mecanico) {
            return response()->json([
                'success' => false,
                'message' => 'Mecánico no encontrado',
            ], 404);
        }

        $mecanico->update($request->validated());

        return new MecanicoResource($mecanico);
    }

    public function destroy(string $id)
    {
        $mecanico = MecanicoEloquentModel::find($id);

        if (!$mecanico) {
            return response()->json([
                'success' => false,
                'message' => 'Mecánico no encontrado',
            ], 404);
        }

        $mecanico->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mecánico eliminado exitosamente',
        ]);
    }
}
