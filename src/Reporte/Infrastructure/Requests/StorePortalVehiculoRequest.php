<?php

namespace Src\Reporte\Infrastructure\Requests;

use App\Support\FieldValidation;
use Illuminate\Foundation\Http\FormRequest;

class StorePortalVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $placa = strtoupper(trim((string) ($this->placa ?? '')));

        $this->merge([
            'placa' => $placa,
            'marca' => trim((string) $this->marca),
            'modelo' => trim((string) $this->modelo),
            'color' => $this->color !== null && $this->color !== '' ? trim((string) $this->color) : null,
            'tipo_combustible' => $this->tipoCombustible ?? $this->tipo_combustible ?? 'gasolina',
            'activo' => true,
        ]);
    }

    public function rules(): array
    {
        return [
            'placa' => FieldValidation::placa(true),
            'marca' => 'required|string|min:2|max:100',
            'modelo' => 'required|string|min:1|max:100',
            'anio' => 'required|integer|min:1950|max:' . (date('Y') + 1),
            'color' => ['nullable', 'string', 'max:50', 'regex:' . FieldValidation::NOMBRE_REGEX],
            'kilometraje' => 'required|integer|min:0|max:9999999',
            'tipo_combustible' => 'required|string|in:gasolina,diesel,hibrido,electrico,gas',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'placa' => 'placa',
            'marca' => 'marca',
            'modelo' => 'modelo',
            'anio' => 'año',
            'color' => 'color',
            'kilometraje' => 'kilometraje',
            'tipo_combustible' => 'tipo de combustible',
            'observaciones' => 'observaciones',
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
