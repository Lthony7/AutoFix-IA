<?php

namespace Src\DiagnosticoIA\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RevisarDiagnosticoIaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'observaciones_revision' => $this->observacionesRevision ?? $this->observaciones_revision,
            'servicio_recomendado' => $this->servicioRecomendado ?? $this->servicio_recomendado,
            'prioridad' => $this->prioridad,
            'coincide_analisis' => $this->has('coincideAnalisis')
                ? filter_var($this->coincideAnalisis, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : ($this->has('coincide_analisis')
                    ? filter_var($this->coincide_analisis, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null),
        ], fn ($v) => $v !== null));
    }

    public function rules(): array
    {
        return [
            'accion' => ['required', 'string', Rule::in(['confirmar', 'modificar', 'descartar'])],
            'observaciones_revision' => [
                Rule::requiredIf(fn () => in_array($this->input('accion'), ['confirmar', 'modificar'], true)),
                'nullable',
                'string',
                'max:5000',
            ],
            'coincide_analisis' => [
                Rule::requiredIf(fn () => in_array($this->input('accion'), ['confirmar', 'modificar'], true)),
                'nullable',
                'boolean',
            ],
            'servicio_recomendado' => 'nullable|string|max:255',
            'prioridad' => 'nullable|string|in:baja,media,alta',
        ];
    }

    public function messages(): array
    {
        return [
            'observaciones_revision.required' => 'Indica tus observaciones al contrastar el diagnóstico IA con tu análisis.',
            'coincide_analisis.required' => 'Indica si el diagnóstico IA coincide con tu análisis.',
        ];
    }
}
