<?php

namespace App\Services;

use RuntimeException;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenRepuestoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;

class OrdenRepuestoStockService
{
    /**
     * @param  list<array<string, mixed>>  $repuestos
     */
    public function aplicarNuevos(OrdenTrabajoEloquentModel $orden, array $repuestos): void
    {
        foreach ($repuestos as $item) {
            $productoId = $item['productoId'] ?? $item['producto_id'] ?? null;
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $precio = $item['precioUnitario'] ?? $item['precio_unitario'] ?? 0;

            if (!$productoId || $cantidad < 1) {
                continue;
            }

            $producto = ProductoEloquentModel::query()->lockForUpdate()->findOrFail($productoId);

            if ((int) $producto->stock < $cantidad) {
                throw new RuntimeException(
                    "Stock insuficiente para \"{$producto->nombre}\". Disponible: {$producto->stock}, solicitado: {$cantidad}."
                );
            }

            $producto->decrement('stock', $cantidad);

            OrdenRepuestoEloquentModel::create([
                'orden_trabajo_id' => $orden->id,
                'producto_id' => $productoId,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $repuestos
     */
    public function reemplazar(OrdenTrabajoEloquentModel $orden, array $repuestos): void
    {
        $orden->loadMissing('ordenRepuestos');
        $this->restaurar($orden);
        $orden->ordenRepuestos()->delete();
        $this->aplicarNuevos($orden, $repuestos);
    }

    public function restaurar(OrdenTrabajoEloquentModel $orden): void
    {
        $orden->loadMissing('ordenRepuestos');

        foreach ($orden->ordenRepuestos as $linea) {
            ProductoEloquentModel::query()
                ->where('id', $linea->producto_id)
                ->increment('stock', (int) $linea->cantidad);
        }
    }
}
