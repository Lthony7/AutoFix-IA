<?php

namespace Src\OrdenTrabajo\Infrastructure\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenRepuestoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ordenTrabajoId' => $this->orden_trabajo_id,
            'productoId' => $this->producto_id,
            'cantidad' => $this->cantidad,
            'precioUnitario' => (float) $this->precio_unitario,
            'producto' => $this->whenLoaded('producto'),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
