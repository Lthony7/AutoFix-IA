<?php

namespace Src\Cita\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReagendarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $fechaHora = $this->fechaHora ?? $this->fecha_hora;
        if (is_string($fechaHora) && str_contains($fechaHora, 'T')) {
            $fechaHora = str_replace('T', ' ', $fechaHora);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $fechaHora)) {
                $fechaHora .= ':00';
            }
        }

        $this->merge(array_filter([
            'fecha_hora' => $fechaHora,
            'notas' => $this->notas,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function rules(): array
    {
        return [
            'fecha_hora' => 'required|date|after:now',
            'notas' => 'nullable|string|max:2000',
        ];
    }
}
