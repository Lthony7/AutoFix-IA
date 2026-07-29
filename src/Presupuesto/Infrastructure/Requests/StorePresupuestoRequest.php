<?php

namespace Src\Presupuesto\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePresupuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'vehiculo_id' => $this->vehiculoId ?? $this->vehiculo_id,
            'notas' => $this->notas,
            'servicios' => $this->servicios ?? [],
            'repuestos' => $this->repuestos ?? [],
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function rules(): array
    {
        return [
            'vehiculo_id' => 'required|uuid|exists:vehiculos,id',
            'notas' => 'nullable|string|max:2000',
            'servicios' => 'nullable|array',
            'servicios.*.servicioId' => 'required_with:servicios|uuid|exists:servicios,id',
            'servicios.*.cantidad' => 'nullable|integer|min:1|max:20',
            'repuestos' => 'nullable|array',
            'repuestos.*.productoId' => 'required_with:repuestos|uuid|exists:productos,id',
            'repuestos.*.cantidad' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'vehiculo_id.required' => 'Selecciona un vehículo para el presupuesto.',
            'servicios.*.servicioId.required_with' => 'Selecciona un servicio válido.',
            'repuestos.*.productoId.required_with' => 'Selecciona un repuesto válido.',
        ];
    }
}
