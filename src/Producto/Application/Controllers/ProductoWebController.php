<?php

namespace Src\Producto\Application\Controllers;

use App\Http\Controllers\Controller;
use App\Support\InertiaTablePaginator;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Producto\Infrastructure\Requests\AjustarStockProductoRequest;
use Src\Producto\Infrastructure\Requests\StoreProductoRequest;
use Src\Producto\Infrastructure\Requests\UpdateProductoRequest;

class ProductoWebController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ProductoEloquentModel::query()
            ->where('tipo_producto', 'repuesto');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'ilike', "%{$search}%")
                    ->orWhere('nombre', 'ilike', "%{$search}%")
                    ->orWhere('proveedor', 'ilike', "%{$search}%");
            });
        }

        if ($categoria = trim((string) $request->query('categoria', ''))) {
            $query->where('categoria', $categoria);
        }

        if ($request->query('stock_bajo') === '1') {
            $query->whereColumn('stock', '<=', 'stock_minimo');
        }

        if ($request->query('activo') === '0') {
            $query->where('activo', false);
        } elseif ($request->query('activo') !== 'all') {
            $query->where('activo', true);
        }

        $paginator = $query
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn (ProductoEloquentModel $model) => [
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
            ]);

        $categorias = ProductoEloquentModel::query()
            ->where('tipo_producto', 'repuesto')
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria')
            ->values()
            ->all();

        return Inertia::render('Repuesto/index', [
            'repuestos' => InertiaTablePaginator::make($paginator),
            'categorias' => $categorias,
            'filters' => [
                'q' => $request->query('q', ''),
                'categoria' => $request->query('categoria', ''),
                'stock_bajo' => $request->query('stock_bajo', ''),
                'activo' => $request->query('activo', '1'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Repuesto/create', [
            'categorias' => $this->categoriasSugeridas(),
        ]);
    }

    public function store(StoreProductoRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['tipo_producto'] = 'repuesto';

            ProductoEloquentModel::create($data);

            return redirect()
                ->route('repuestos.index')
                ->with('success', 'Repuesto registrado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al registrar el repuesto: '.$e->getMessage());
        }
    }

    public function edit(string $id): Response
    {
        $repuesto = ProductoEloquentModel::where('tipo_producto', 'repuesto')->findOrFail($id);

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
            'categorias' => $this->categoriasSugeridas(),
        ]);
    }

    public function update(UpdateProductoRequest $request, string $id): RedirectResponse
    {
        try {
            $repuesto = ProductoEloquentModel::where('tipo_producto', 'repuesto')->findOrFail($id);
            $data = $request->validated();
            $data['tipo_producto'] = 'repuesto';
            $repuesto->update($data);

            return redirect()
                ->route('repuestos.index')
                ->with('success', 'Repuesto actualizado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el repuesto: '.$e->getMessage());
        }
    }

    public function ajustarStock(AjustarStockProductoRequest $request, string $id): RedirectResponse
    {
        $repuesto = ProductoEloquentModel::where('tipo_producto', 'repuesto')->findOrFail($id);
        $repuesto->update(['stock' => $request->validated('stock')]);

        return redirect()
            ->back()
            ->with('success', "Stock de {$repuesto->nombre} actualizado a {$repuesto->stock}");
    }

    public function destroy(string $id): RedirectResponse
    {
        $repuesto = ProductoEloquentModel::where('tipo_producto', 'repuesto')->find($id);

        if (!$repuesto) {
            return redirect()->back()->with('error', 'Repuesto no encontrado');
        }

        $usadoEnOrdenes = DB::table('orden_repuesto')
            ->where('producto_id', $repuesto->id)
            ->exists();

        if ($usadoEnOrdenes) {
            $repuesto->update(['activo' => false]);

            return redirect()
                ->route('repuestos.index')
                ->with('success', 'El repuesto ya se usó en órdenes: se desactivó (no se eliminó) para conservar el historial.');
        }

        $repuesto->delete();

        return redirect()
            ->route('repuestos.index')
            ->with('success', 'Repuesto eliminado exitosamente');
    }

    /** @return list<string> */
    private function categoriasSugeridas(): array
    {
        $sugeridas = [
            'Lubricantes',
            'Filtros',
            'Frenos',
            'Suspensión',
            'Eléctrico',
            'Motor',
            'Inyección',
            'Transmisión',
            'Clima',
            'Escape',
            'Llantas',
            'Otros',
        ];

        $existentes = ProductoEloquentModel::query()
            ->where('tipo_producto', 'repuesto')
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->pluck('categoria')
            ->all();

        return array_values(array_unique(array_merge($sugeridas, $existentes)));
    }
}
