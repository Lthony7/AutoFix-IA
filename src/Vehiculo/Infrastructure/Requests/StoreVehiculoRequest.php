<?php

namespace Src\Vehiculo\Infrastructure\Requests;

use App\Support\FieldValidation;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $placa = strtoupper(trim((string) ($this->placa ?? '')));

        $this->merge([
            'cliente_id' => $this->clienteId ?? $this->cliente_id,
            'placa' => $placa,
            'marca' => trim((string) $this->marca),
            'modelo' => trim((string) $this->modelo),
            'color' => $this->color !== null && $this->color !== '' ? trim((string) $this->color) : null,
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
            'placa' => FieldValidation::placa(true),
            'marca' => 'required|string|min:2|max:100',
            'modelo' => 'required|string|min:1|max:100',
            'anio' => 'required|integer|min:1950|max:' . (date('Y') + 1),
            'color' => ['nullable', 'string', 'max:50', 'regex:' . FieldValidation::NOMBRE_REGEX],
            'kilometraje' => 'required|integer|min:0|max:9999999',
            'tipo_combustible' => 'required|string|in:gasolina,diesel,hibrido,electrico,gas',
            'observaciones' => 'nullable|string|max:1000',
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

    public function messages(): array
    {
        return array_merge(FieldValidation::messages(), [
            'cliente_id.required' => 'Debes seleccionar un cliente',
            'placa.unique' => 'Esta placa ya está registrada',
            'color.regex' => 'El color solo puede contener letras',
            'anio.min' => 'El año del vehículo no es válido',
            'anio.max' => 'El año del vehículo no es válido',
        ]);
    }
}
