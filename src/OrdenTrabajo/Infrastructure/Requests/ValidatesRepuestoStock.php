<?php

namespace Src\OrdenTrabajo\Infrastructure\Requests;

use Illuminate\Validation\Validator;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenRepuestoEloquentModel;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;

trait ValidatesRepuestoStock
{
    protected function validateRepuestoStock(Validator $validator): void
    {
        $repuestos = $this->input('repuestos');
        if (!is_array($repuestos) || $repuestos === []) {
            return;
        }

        $ordenId = $this->route('orden')
            ?? $this->route('id')
            ?? $this->route('orden_trabajo');

        $solicitado = [];
        foreach ($repuestos as $item) {
            $productoId = $item['productoId'] ?? $item['producto_id'] ?? null;
            $cantidad = (int) ($item['cantidad'] ?? 0);
            if (!$productoId || $cantidad < 1) {
                continue;
            }
            $solicitado[$productoId] = ($solicitado[$productoId] ?? 0) + $cantidad;
        }

        foreach ($solicitado as $productoId => $cantidad) {
            $producto = ProductoEloquentModel::query()->find($productoId);
            if (!$producto) {
                continue;
            }

            $disponible = (int) $producto->stock;
            if ($ordenId) {
                $disponible += (int) OrdenRepuestoEloquentModel::query()
                    ->where('orden_trabajo_id', $ordenId)
                    ->where('producto_id', $productoId)
                    ->sum('cantidad');
            }

            if ($cantidad > $disponible) {
                $validator->errors()->add(
                    'repuestos',
                    "Stock insuficiente para \"{$producto->nombre}\". Disponible: {$disponible}, solicitado: {$cantidad}."
                );
            }
        }
    }
}
