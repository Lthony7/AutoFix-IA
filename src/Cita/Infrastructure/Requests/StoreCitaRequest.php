<?php

namespace Src\Cita\Infrastructure\Requests;

use App\Enums\CitaTipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $fechaHora = $this->fechaHora ?? $this->fecha_hora;
        // datetime-local llega como "YYYY-MM-DDTHH:mm"
        if (is_string($fechaHora) && str_contains($fechaHora, 'T')) {
            $fechaHora = str_replace('T', ' ', $fechaHora);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $fechaHora)) {
                $fechaHora .= ':00';
            }
        }

        $this->merge(array_filter([
            'cliente_id' => $this->clienteId ?? $this->cliente_id,
            'vehiculo_id' => $this->vehiculoId ?? $this->vehiculo_id,
            'mecanico_id' => ($this->mecanicoId ?? $this->mecanico_id) ?: null,
            'orden_trabajo_id' => ($this->ordenTrabajoId ?? $this->orden_trabajo_id) ?: null,
            'fecha_hora' => $fechaHora,
            'duracion_minutos' => $this->duracionMinutos ?? $this->duracion_minutos ?? 60,
            'tipo' => $this->tipo,
            'notas' => $this->notas,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function rules(): array
    {
        return [
            'cliente_id' => 'required|uuid|exists:clientes,id',
            'vehiculo_id' => 'required|uuid|exists:vehiculos,id',
            'mecanico_id' => 'nullable|uuid|exists:mecanicos,id',
            'orden_trabajo_id' => 'nullable|uuid|exists:ordenes_trabajo,id',
            'fecha_hora' => 'required|date|after:now',
            'duracion_minutos' => 'nullable|integer|min:15|max:480',
            'tipo' => ['required', 'string', Rule::in(CitaTipo::values())],
            'notas' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'Selecciona un cliente.',
            'vehiculo_id.required' => 'Selecciona un vehículo.',
            'fecha_hora.required' => 'Indica la fecha y hora de la cita.',
            'fecha_hora.after' => 'La cita debe programarse en una fecha/hora futura.',
            'tipo.required' => 'Selecciona el tipo de cita.',
        ];
    }
}
