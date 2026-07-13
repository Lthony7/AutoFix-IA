<?php

namespace Src\Vehiculo\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('clienteId')) {
            $data['cliente_id'] = $this->clienteId;
        }

        if ($this->has('tipoCombustible')) {
            $data['tipo_combustible'] = $this->tipoCombustible;
        }

        if ($this->has('activo')) {
            $data['activo'] = filter_var($this->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $vehiculoId = $this->route('id') ?? $this->route('vehiculo');

        return [
            'cliente_id' => 'sometimes|uuid|exists:clientes,id',
            'placa' => 'sometimes|string|max:20|unique:vehiculos,placa,' . $vehiculoId . ',id',
            'marca' => 'sometimes|string|max:100',
            'modelo' => 'sometimes|string|max:100',
            'anio' => 'sometimes|integer|min:1950|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'kilometraje' => 'sometimes|integer|min:0',
            'tipo_combustible' => 'sometimes|string|in:gasolina,diesel,hibrido,electrico,gas',
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
            'kilometraje' => 'kilometraje',
            'tipo_combustible' => 'tipo de combustible',
        ];
    }
}
