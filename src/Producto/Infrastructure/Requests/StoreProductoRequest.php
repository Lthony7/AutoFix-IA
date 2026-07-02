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
            'tipo_producto' => $this->tipoProducto,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,
            'stock' => $this->stock,
            'activo' => $this->activo,
        ]);
    }

    public function rules(): array
    {
        return [
            'tipo_producto' => 'required|string|in:tipo1,tipo2,tipo3',
            'codigo' => 'required|string|unique:productos,codigo',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
            'activo' => 'required|boolean'
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
            'stock.required' => 'El stock es obligatorio',
            'activo.required' => 'El estado activo es obligatorio'
        ];
    }
}
