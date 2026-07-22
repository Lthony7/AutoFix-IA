<?php

namespace Src\Pago\Infrastructure\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ordenTrabajoId' => $this->orden_trabajo_id,
            'facturaId' => $this->factura_id,
            'valorServicios' => (float) $this->valor_servicios,
            'valorRepuestos' => (float) $this->valor_repuestos,
            'descuento' => (float) $this->descuento,
            'total' => (float) $this->total,
            'estado' => $this->estado instanceof \BackedEnum ? $this->estado->value : $this->estado,
            'estadoLabel' => $this->estado instanceof \BackedEnum ? $this->estado->label() : $this->estado,
            'metodoPago' => $this->metodo_pago,
            'registradoPor' => $this->registrado_por,
            'ordenTrabajo' => $this->whenLoaded('ordenTrabajo'),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
