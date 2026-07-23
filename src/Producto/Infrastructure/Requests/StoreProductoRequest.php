<?php

namespace Src\Producto\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Convert camelCase to snake_case for validation
        $this->merge([
            'tipo_producto' => $this->tipoProducto ?? $this->tipo_producto,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,
            'stock' => $this->stock,
            'stock_minimo' => $this->stockMinimo ?? $this->stock_minimo ?? 0,
            'activo' => $this->activo,
            'categoria' => $this->categoria,
            'proveedor' => $this->proveedor,
        ]);
    }

    public function rules(): array
    {
        return [
            'tipo_producto' => 'required|string|in:tipo1,tipo2,tipo3',
            'codigo' => 'required|string|unique:productos,codigo',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0|max:999999999',
            'stock' => 'required|integer|min:0|max:999999',
            'stock_minimo' => 'sometimes|integer|min:0|max:999999',
            'activo' => 'required|boolean',
            'categoria' => 'nullable|string|max:255',
            'proveedor' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_producto' => 'tipo de producto',
            'codigo' => 'código',
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'precio' => 'precio',
            'stock' => 'stock',
            'activo' => 'activo',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_producto.required' => 'El tipo de producto es obligatorio',
            'tipo_producto.in' => 'El tipo de producto debe ser tipo1, tipo2 o tipo3',
            'codigo.required' => 'El código es obligatorio',
            'codigo.unique' => 'Este código ya está registrado',
            'nombre.required' => 'El nombre es obligatorio',
            'descripcion.required' => 'La descripción es obligatoria',
            'precio.required' => 'El precio es obligatorio',
            'precio.min' => 'El precio no puede ser negativo',
            'stock.required' => 'El stock es obligatorio',
            'stock.min' => 'El stock no puede ser negativo',
            'activo.required' => 'El estado activo es obligatorio',
        ];
    }
}

