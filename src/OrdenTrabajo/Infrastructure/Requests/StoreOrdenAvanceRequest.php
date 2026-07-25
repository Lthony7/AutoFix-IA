<?php

namespace Src\OrdenTrabajo\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrdenAvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mensaje' => trim((string) $this->mensaje),
        ]);
    }

    public function rules(): array
    {
        return [
            'mensaje' => 'required|string|min:3|max:2000',
        ];
    }

    public function attributes(): array
    {
        return [
            'mensaje' => 'avance',
        ];
    }

    public function messages(): array
    {
        return [
            'mensaje.required' => 'Describe el avance del servicio',
            'mensaje.min' => 'El avance debe tener al menos 3 caracteres',
        ];
    }
}
