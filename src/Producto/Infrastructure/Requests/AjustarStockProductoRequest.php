<?php

namespace Src\Producto\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjustarStockProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'stock' => $this->stock,
            'motivo' => $this->motivo,
        ]);
    }

    public function rules(): array
    {
        return [
            'stock' => 'required|integer|min:0|max:999999',
            'motivo' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'stock' => 'stock',
            'motivo' => 'motivo',
        ];
    }
}
