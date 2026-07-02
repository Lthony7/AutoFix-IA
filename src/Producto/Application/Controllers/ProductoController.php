<?php

namespace Src\Producto\Application\Controllers;

use App\Http\Controllers\Controller;
use Src\Producto\Infrastructure\Resources\ProductoResource;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Producto\Infrastructure\Requests\StoreProductoRequest;
use Src\Producto\Infrastructure\Requests\UpdateProductoRequest;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = ProductoEloquentModel::all();
        return ProductoResource::collection($productos);
    }

    public function store(StoreProductoRequest $request)
    {
        $producto = ProductoEloquentModel::create($request->validated());
        return new ProductoResource($producto);
    }

    public function show(string $id)
    {
        $producto = ProductoEloquentModel::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el producto'
            ], 404);
        }

        return new ProductoResource($producto);
    }

    public function update(UpdateProductoRequest $request, string $id)
    {
        $producto = ProductoEloquentModel::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el producto'
            ], 404);
        }

        $producto->update($request->validated());
        return new ProductoResource($producto);
    }

    public function destroy(string $id)
    {
        $producto = ProductoEloquentModel::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'producto no encontrado'
            ], 404);
        }

        $producto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado exitosamente'
        ], 200);
    }

}
