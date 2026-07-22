<?php

namespace Src\DiagnosticoIA\Infrastructure\Requests;

use App\Enums\SugerenciaIaEstado;
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
        ], fn ($v) => $v !== null));
    }

    public function rules(): array
    {
        return [
            'accion' => ['required', 'string', Rule::in(['confirmar', 'modificar', 'descartar'])],
            'observaciones_revision' => 'nullable|string',
            'servicio_recomendado' => 'nullable|string|max:255',
            'prioridad' => 'nullable|string|in:baja,media,alta',
        ];
    }
}
