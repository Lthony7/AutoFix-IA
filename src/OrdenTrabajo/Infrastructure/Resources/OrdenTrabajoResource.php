<?php

namespace Src\OrdenTrabajo\Infrastructure\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenTrabajoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'clienteId' => $this->cliente_id,
            'vehiculoId' => $this->vehiculo_id,
            'mecanicoId' => $this->mecanico_id,
            'createdBy' => $this->created_by,
            'estado' => $this->estado instanceof \BackedEnum ? $this->estado->value : $this->estado,
            'estadoLabel' => $this->estado instanceof \BackedEnum ? $this->estado->label() : $this->estado,
            'tipoFalla' => $this->tipo_falla,
            'fallaReportada' => $this->falla_reportada,
            'kilometrajeIngreso' => $this->kilometraje_ingreso,
            'observaciones' => $this->observaciones,
            'diagnosticoTecnico' => $this->diagnostico_tecnico,
            'prioridad' => $this->prioridad,
            'cliente' => $this->whenLoaded('cliente'),
            'vehiculo' => $this->whenLoaded('vehiculo'),
            'mecanico' => $this->whenLoaded('mecanico'),
            'servicios' => OrdenServicioResource::collection($this->whenLoaded('ordenServicios')),
            'repuestos' => OrdenRepuestoResource::collection($this->whenLoaded('ordenRepuestos')),
            'sugerenciaIa' => $this->whenLoaded('sugerenciaIa'),
            'pago' => $this->whenLoaded('pago'),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
