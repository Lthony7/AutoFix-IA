<?php

namespace Src\Vehiculo\Application\Controllers;

use App\Http\Controllers\Controller;
use App\Support\InertiaTablePaginator;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Vehiculo\Infrastructure\Mappers\VehiculoMapper;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;
use Src\Vehiculo\Infrastructure\Requests\StoreVehiculoRequest;
use Src\Vehiculo\Infrastructure\Requests\UpdateVehiculoRequest;

class VehiculoWebController extends Controller
{
    public function index(): Response
    {
        $paginator = VehiculoEloquentModel::with('cliente')
            ->orderBy('placa')
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn ($model) => VehiculoMapper::toDomain($model)->toArray());

        return Inertia::render('Vehiculo/index', [
            'vehiculos' => InertiaTablePaginator::make($paginator),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Vehiculo/create', [
            'clientes' => $this->clientesOptions(),
        ]);
    }

    public function store(StoreVehiculoRequest $request): RedirectResponse
    {
        try {
            VehiculoEloquentModel::create($request->validated());

            return redirect()
                ->route('vehiculos.index')
                ->with('success', 'Vehículo registrado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al registrar el vehículo: ' . $e->getMessage());
        }
    }

    public function edit(string $id): Response
    {
        $vehiculo = VehiculoEloquentModel::with('cliente')->findOrFail($id);

        return Inertia::render('Vehiculo/edit', [
            'vehiculo' => VehiculoMapper::toDomain($vehiculo)->toArray(),
            'clientes' => $this->clientesOptions(),
        ]);
    }

    public function update(UpdateVehiculoRequest $request, string $id): RedirectResponse
    {
        try {
            $vehiculo = VehiculoEloquentModel::findOrFail($id);
            $vehiculo->update($request->validated());

            return redirect()
                ->route('vehiculos.index')
                ->with('success', 'Vehículo actualizado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el vehículo: ' . $e->getMessage());
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $vehiculo = VehiculoEloquentModel::find($id);

        if (!$vehiculo) {
            return redirect()->back()->with('error', 'Vehículo no encontrado');
        }

        $vehiculo->delete();

        return redirect()
            ->route('vehiculos.index')
            ->with('success', 'Vehículo eliminado exitosamente');
    }

    private function clientesOptions(): array
    {
        return ClienteEloquentModel::query()
            ->where('estado', true)
            ->orderBy('razon_social')
            ->get()
            ->map(function (ClienteEloquentModel $cliente) {
                $completo = trim(($cliente->nombres ?? '') . ' ' . ($cliente->apellidos ?? ''));

                return [
                    'id' => $cliente->id,
                    'label' => ($completo !== '' ? $completo : $cliente->razon_social) . ' — ' . $cliente->numero_documento,
                ];
            })
            ->values()
            ->toArray();
    }
}
