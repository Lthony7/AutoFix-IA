<?php

namespace Src\OrdenTrabajo\Infrastructure\Requests;

use App\Enums\OrdenEstado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'estado' => $this->estado,
        ]);
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'string', Rule::in(OrdenEstado::values())],
        ];
    }
}
