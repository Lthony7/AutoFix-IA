<?php

namespace Src\Servicio\Application\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Servicio\Infrastructure\Mappers\ServicioMapper;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;
use Src\Servicio\Infrastructure\Requests\StoreServicioRequest;
use Src\Servicio\Infrastructure\Requests\UpdateServicioRequest;

class ServicioWebController extends Controller
{
    public function index(): Response
    {
        $servicios = ServicioEloquentModel::orderBy('nombre')->get();

        $data = $servicios->map(
            fn ($model) => ServicioMapper::toDomain($model)->toArray()
        )->toArray();

        return Inertia::render('Servicio/index', [
            'servicios' => [
                'data' => $data,
                'meta' => ['total' => count($data)],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Servicio/create');
    }

    public function store(StoreServicioRequest $request): RedirectResponse
    {
        try {
            ServicioEloquentModel::create($request->validated());

            return redirect()
                ->route('servicios.index')
                ->with('success', 'Servicio creado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el servicio: ' . $e->getMessage());
        }
    }

    public function edit(string $id): Response
    {
        $servicio = ServicioEloquentModel::findOrFail($id);

        return Inertia::render('Servicio/edit', [
            'servicio' => ServicioMapper::toDomain($servicio)->toArray(),
        ]);
    }

    public function update(UpdateServicioRequest $request, string $id): RedirectResponse
    {
        try {
            $servicio = ServicioEloquentModel::findOrFail($id);
            $servicio->update($request->validated());

            return redirect()
                ->route('servicios.index')
                ->with('success', 'Servicio actualizado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el servicio: ' . $e->getMessage());
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $servicio = ServicioEloquentModel::find($id);

        if (!$servicio) {
            return redirect()->back()->with('error', 'Servicio no encontrado');
        }

        $servicio->delete();

        return redirect()
            ->route('servicios.index')
            ->with('success', 'Servicio eliminado exitosamente');
    }
}
