<?php

namespace Src\DiagnosticoIA\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosticoIaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'orden_trabajo_id' => $this->ordenTrabajoId ?? $this->orden_trabajo_id,
            'tipo_falla' => $this->tipoFalla ?? $this->tipo_falla,
            'descripcion' => $this->descripcion,
            'momento' => $this->momento,
            'luces_tablero' => $this->lucesTablero ?? $this->luces_tablero,
            'ruidos' => $this->ruidos,
            'puede_circular' => $this->has('puedeCircular') || $this->has('puede_circular')
                ? filter_var($this->puedeCircular ?? $this->puede_circular, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null,
            'urgencia' => $this->urgencia,
            'observaciones' => $this->observaciones,
        ]);
    }

    public function rules(): array
    {
        return [
            'orden_trabajo_id' => 'required|uuid|exists:ordenes_trabajo,id|unique:diagnosticos_ia,orden_trabajo_id',
            'tipo_falla' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'momento' => 'required|string|max:255',
            'luces_tablero' => 'nullable|string|max:255',
            'ruidos' => 'nullable|string|max:255',
            'puede_circular' => 'required|boolean',
            'urgencia' => 'required|string|in:baja,media,alta',
            'observaciones' => 'nullable|string',
        ];
    }
}
