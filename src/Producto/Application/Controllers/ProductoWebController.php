<?php

namespace Src\Producto\Application\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Producto\Infrastructure\Requests\StoreProductoRequest;
use Src\Producto\Infrastructure\Requests\UpdateProductoRequest;

class ProductoWebController extends Controller
{
    public function index(): Response
    {
        $repuestos = ProductoEloquentModel::orderBy('nombre')->get();

        $data = $repuestos->map(fn (ProductoEloquentModel $model) => [
            'id' => $model->id,
            'codigo' => $model->codigo,
            'nombre' => $model->nombre,
            'descripcion' => $model->descripcion,
            'precio' => (float) $model->precio,
            'stock' => $model->stock,
            'stockMinimo' => $model->stock_minimo ?? 0,
            'activo' => (bool) $model->activo,
            'tipoProducto' => $model->tipo_producto,
            'categoria' => $model->categoria,
            'proveedor' => $model->proveedor,
            'createdAt' => $model->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $model->updated_at?->format('Y-m-d H:i:s'),
        ])->toArray();

        return Inertia::render('Repuesto/index', [
            'repuestos' => [
                'data' => $data,
                'meta' => ['total' => count($data)],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Repuesto/create');
    }

    public function store(StoreProductoRequest $request): RedirectResponse
    {
        try {
            ProductoEloquentModel::create($request->validated());

            return redirect()
                ->route('repuestos.index')
                ->with('success', 'Repuesto registrado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al registrar el repuesto: ' . $e->getMessage());
        }
    }

    public function edit(string $id): Response
    {
        $repuesto = ProductoEloquentModel::findOrFail($id);

        return Inertia::render('Repuesto/edit', [
            'repuesto' => [
                'id' => $repuesto->id,
                'codigo' => $repuesto->codigo,
                'nombre' => $repuesto->nombre,
                'descripcion' => $repuesto->descripcion,
                'precio' => (float) $repuesto->precio,
                'stock' => $repuesto->stock,
                'stockMinimo' => $repuesto->stock_minimo ?? 0,
                'activo' => (bool) $repuesto->activo,
                'tipoProducto' => $repuesto->tipo_producto,
                'categoria' => $repuesto->categoria,
                'proveedor' => $repuesto->proveedor,
            ],
        ]);
    }

    public function update(UpdateProductoRequest $request, string $id): RedirectResponse
    {
        try {
            $repuesto = ProductoEloquentModel::findOrFail($id);
            $repuesto->update($request->validated());

            return redirect()
                ->route('repuestos.index')
                ->with('success', 'Repuesto actualizado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el repuesto: ' . $e->getMessage());
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $repuesto = ProductoEloquentModel::find($id);

        if (!$repuesto) {
            return redirect()->back()->with('error', 'Repuesto no encontrado');
        }

        $repuesto->delete();

        return redirect()
            ->route('repuestos.index')
            ->with('success', 'Repuesto eliminado exitosamente');
    }
}
