<?php

namespace Src\Factura\Infrastructure\Requests;

use App\Enums\FacturaEstado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('fechaEmision')) {
            $data['fecha_emision'] = $this->fechaEmision;
        }

        if ($this->has('observaciones') || $this->has('descuento') || $this->has('estado') || $this->has('serie')) {
            if ($this->has('descuento')) {
                $data['descuento'] = $this->descuento;
            }
            if ($this->has('estado')) {
                $data['estado'] = $this->estado;
            }
            if ($this->has('serie')) {
                $data['serie'] = $this->serie;
            }
            if ($this->has('observaciones')) {
                $data['observaciones'] = $this->observaciones;
            }
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'serie' => 'sometimes|string|max:20',
            'fecha_emision' => 'sometimes|date',
            'descuento' => 'sometimes|numeric|min:0',
            'estado' => ['sometimes', 'string', Rule::in(FacturaEstado::values())],
            'observaciones' => 'nullable|string',
        ];
    }
}
