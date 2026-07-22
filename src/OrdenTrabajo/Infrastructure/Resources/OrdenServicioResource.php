<?php

namespace Src\OrdenTrabajo\Infrastructure\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenServicioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ordenTrabajoId' => $this->orden_trabajo_id,
            'servicioId' => $this->servicio_id,
            'precio' => (float) $this->precio,
            'servicio' => $this->whenLoaded('servicio'),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
