<?php

namespace Src\Producto\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Convert camelCase to snake_case for validation only if fields are present
        $data = [];

        if ($this->has('codigo')) {
            $data['codigo'] = $this->codigo;
        }
    
        if ($this->has('nombre')) {
            $data['nombre'] = $this->nombre;
        }

        if ($this->has('descripcion')) {
            $data['descripcion'] = $this->descripcion;
        }

        if ($this->has('precio')) {
            $data['precio'] = $this->precio;
        }

        if ($this->has('stock')) {
            $data['stock'] = $this->stock;
        }

        if ($this->has('activo')) {
            $data['activo'] = $this->activo;
        }

        if ($this->has('tipo_producto')) {
            $data['tipo_producto'] = $this->tipoProducto;
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        // Obtener el ID desde la ruta (puede ser 'id' o 'cliente' dependiendo de si es web o API)
       // $productoId = $this->route('id') ?? $this->route('producto');

        return [
            'tipo_producto' => 'sometimes|string|in:tipo1,tipo2,tipo3',
            'codigo' => 'sometimes|string|unique:productos,codigo',
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'sometimes|string|max:255',
            'precio' => 'sometimes|numeric',
            'stock' => 'sometimes|integer',
            'activo' => 'sometimes|boolean'
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
