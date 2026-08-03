<?php

namespace Src\Cliente\Application\Controllers;

use App\Http\Controllers\Controller;
use App\Support\InertiaTablePaginator;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Cliente\Infrastructure\Mappers\ClienteMapper;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Cliente\Infrastructure\Requests\StoreClienteRequest;
use Src\Cliente\Infrastructure\Requests\UpdateClienteRequest;

class ClienteWebController extends Controller
{
    public function index(): Response
    {
        $paginator = ClienteEloquentModel::query()
            ->with(['vehiculos' => fn ($q) => $q->orderBy('placa')])
            ->orderBy('razon_social')
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(function ($model) {
                $data = ClienteMapper::toDomain($model)->toArray();
                $data['vehiculos'] = $model->vehiculos->map(fn ($v) => [
                    'id' => $v->id,
                    'placa' => $v->placa,
                    'marca' => $v->marca,
                    'modelo' => $v->modelo,
                    'anio' => $v->anio,
                    'color' => $v->color,
                ])->values()->toArray();

                return $data;
            });

        return Inertia::render('Cliente/index', [
            'customers' => InertiaTablePaginator::make($paginator),
            'stats' => [
                'total' => ClienteEloquentModel::count(),
                'active' => ClienteEloquentModel::where('estado', true)->count(),
                'inactive' => ClienteEloquentModel::where('estado', false)->count(),
                'month' => ClienteEloquentModel::where('created_at', '>=', now()->copy()->startOfMonth())->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Cliente/create');
    }

    public function store(StoreClienteRequest $request): RedirectResponse
    {
        try {
            ClienteEloquentModel::create($request->validated());

            return redirect()
                ->route('clientes.index')
                ->with('success', 'Cliente creado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el cliente: ' . $e->getMessage());
        }
    }

    public function edit(string $id): Response
    {
        $cliente = ClienteEloquentModel::findOrFail($id);

        return Inertia::render('Cliente/edit', [
            'cliente' => ClienteMapper::toDomain($cliente)->toArray(),
        ]);
    }

    public function update(UpdateClienteRequest $request, string $id): RedirectResponse
    {
        try {
            $cliente = ClienteEloquentModel::findOrFail($id);
            $cliente->update($request->validated());

            return redirect()
                ->route('clientes.index')
                ->with('success', 'Cliente actualizado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el cliente: ' . $e->getMessage());
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $cliente = ClienteEloquentModel::find($id);

        if (!$cliente) {
            return redirect()
                ->back()
                ->with('error', 'Cliente no encontrado');
        }

        if ($cliente->vehiculos()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar este cliente porque tiene vehículos asociados');
        }

        $cliente->delete();

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente eliminado exitosamente');
    }
}
