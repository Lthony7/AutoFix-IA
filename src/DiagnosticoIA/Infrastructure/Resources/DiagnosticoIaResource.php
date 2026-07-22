<?php

namespace Src\DiagnosticoIA\Infrastructure\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosticoIaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ordenTrabajoId' => $this->orden_trabajo_id,
            'inputData' => $this->input_data,
            'respuestaCompleta' => $this->respuesta_completa,
            'posiblesCausas' => $this->posibles_causas,
            'servicioRecomendado' => $this->servicio_recomendado,
            'prioridad' => $this->prioridad,
            'observacionMecanico' => $this->observacion_mecanico,
            'advertencia' => $this->advertencia,
            'estado' => $this->estado instanceof \BackedEnum ? $this->estado->value : $this->estado,
            'estadoLabel' => $this->estado instanceof \BackedEnum ? $this->estado->label() : $this->estado,
            'esSimulado' => (bool) $this->es_simulado,
            'observacionesRevision' => $this->observaciones_revision,
            'ordenTrabajo' => $this->whenLoaded('ordenTrabajo'),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
