<?php

namespace Src\Servicio\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'precio_base' => $this->precioBase ?? $this->precio_base,
            'activo' => $this->has('activo')
                ? filter_var($this->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
                : true,
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255|unique:servicios,nombre',
            'descripcion' => 'nullable|string',
            'precio_base' => 'required|numeric|min:0|max:999999999',
            'activo' => 'sometimes|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'precio_base' => 'precio base',
            'activo' => 'activo',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del servicio es obligatorio',
            'nombre.unique' => 'Ya existe un servicio con ese nombre',
            'precio_base.required' => 'El precio base es obligatorio',
            'precio_base.min' => 'El precio base no puede ser negativo',
        ];
    }
}

