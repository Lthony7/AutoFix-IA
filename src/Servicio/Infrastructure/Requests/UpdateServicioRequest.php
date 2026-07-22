<?php

namespace Src\Servicio\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServicioRequest extends FormRequest
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
                ? filter_var($this->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null,
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('servicio') ?? $this->route('id');

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('servicios', 'nombre')->ignore($id),
            ],
            'descripcion' => 'nullable|string',
            'precio_base' => 'sometimes|required|numeric|min:0',
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
}
