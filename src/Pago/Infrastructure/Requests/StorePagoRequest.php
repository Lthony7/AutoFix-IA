<?php

namespace Src\Pago\Infrastructure\Requests;

use App\Enums\PagoEstado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'orden_trabajo_id' => $this->ordenTrabajoId ?? $this->orden_trabajo_id,
            'valor_servicios' => $this->valorServicios ?? $this->valor_servicios,
            'valor_repuestos' => $this->valorRepuestos ?? $this->valor_repuestos,
            'metodo_pago' => $this->metodoPago ?? $this->metodo_pago,
            'registrado_por' => $this->user()?->id,
        ], fn ($v) => $v !== null));
    }

    public function rules(): array
    {
        return [
            'orden_trabajo_id' => 'required|uuid|exists:ordenes_trabajo,id|unique:pagos,orden_trabajo_id',
            'valor_servicios' => 'nullable|numeric|min:0',
            'valor_repuestos' => 'nullable|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'estado' => ['sometimes', 'string', Rule::in(PagoEstado::values())],
            'metodo_pago' => 'nullable|string|max:255',
            'registrado_por' => 'nullable|uuid|exists:users,id',
        ];
    }
}
