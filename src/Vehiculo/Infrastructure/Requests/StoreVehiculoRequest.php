<?php

namespace Src\Vehiculo\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cliente_id' => $this->clienteId ?? $this->cliente_id,
            'tipo_combustible' => $this->tipoCombustible ?? $this->tipo_combustible ?? 'gasolina',
            'activo' => $this->has('activo')
                ? filter_var($this->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
                : true,
        ]);
    }

    public function rules(): array
    {
        return [
            'cliente_id' => 'required|uuid|exists:clientes,id',
            'placa' => 'required|string|max:20|unique:vehiculos,placa',
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'anio' => 'required|integer|min:1950|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'kilometraje' => 'required|integer|min:0',
            'tipo_combustible' => 'required|string|in:gasolina,diesel,hibrido,electrico,gas',
            'observaciones' => 'nullable|string',
            'activo' => 'sometimes|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'cliente_id' => 'cliente',
            'placa' => 'placa',
            'marca' => 'marca',
            'modelo' => 'modelo',
            'anio' => 'año',
            'color' => 'color',
            'kilometraje' => 'kilometraje',
            'tipo_combustible' => 'tipo de combustible',
            'observaciones' => 'observaciones',
            'activo' => 'estado',
        ];
    }
}
