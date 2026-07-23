<?php

namespace Src\Vehiculo\Infrastructure\Requests;

use App\Support\FieldValidation;
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

        if ($this->has('clienteId') || $this->has('cliente_id')) {
            $data['cliente_id'] = $this->clienteId ?? $this->cliente_id;
        }

        if ($this->has('placa')) {
            $data['placa'] = strtoupper(trim((string) $this->placa));
        }

        if ($this->has('marca')) {
            $data['marca'] = trim((string) $this->marca);
        }

        if ($this->has('modelo')) {
            $data['modelo'] = trim((string) $this->modelo);
        }

        if ($this->has('color')) {
            $data['color'] = $this->color !== null && $this->color !== ''
                ? trim((string) $this->color)
                : null;
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
            'placa' => FieldValidation::placa(false, $vehiculoId),
            'marca' => 'sometimes|string|min:2|max:100',
            'modelo' => 'sometimes|string|min:1|max:100',
            'anio' => 'sometimes|integer|min:1950|max:' . (date('Y') + 1),
            'color' => ['nullable', 'string', 'max:50', 'regex:' . FieldValidation::NOMBRE_REGEX],
            'kilometraje' => 'sometimes|integer|min:0|max:9999999',
            'tipo_combustible' => 'sometimes|string|in:gasolina,diesel,hibrido,electrico,gas',
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
        ];
    }

    public function messages(): array
    {
        return array_merge(FieldValidation::messages(), [
            'placa.unique' => 'Esta placa ya está registrada',
            'color.regex' => 'El color solo puede contener letras',
        ]);
    }
}
