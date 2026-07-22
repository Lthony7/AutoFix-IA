<?php

namespace Src\OrdenTrabajo\Infrastructure\Requests;

use App\Enums\OrdenEstado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignarMecanicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mecanico_id' => $this->mecanicoId ?? $this->mecanico_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'mecanico_id' => 'required|uuid|exists:mecanicos,id',
        ];
    }
}
