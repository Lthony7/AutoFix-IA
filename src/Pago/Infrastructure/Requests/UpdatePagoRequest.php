<?php

namespace Src\Pago\Infrastructure\Requests;

use App\Enums\PagoEstado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'metodo_pago' => $this->metodoPago ?? $this->metodo_pago,
        ], fn ($v) => $v !== null && $v !== ''));

        if ($this->has('metodoPago') && ($this->metodoPago === null || $this->metodoPago === '')) {
            $this->merge(['metodo_pago' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'descuento' => 'nullable|numeric|min:0',
            'estado' => ['sometimes', 'string', Rule::in(PagoEstado::values())],
            'metodo_pago' => 'nullable|string|max:255',
        ];
    }
}
