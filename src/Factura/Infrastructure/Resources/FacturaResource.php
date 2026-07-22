<?php

namespace Src\Factura\Infrastructure\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacturaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'serie' => $this->serie,
            'ordenTrabajoId' => $this->orden_trabajo_id,
            'ordenNumero' => $this->whenLoaded('ordenTrabajo', fn () => $this->ordenTrabajo?->numero),
            'clienteId' => $this->cliente_id,
            'clienteNombre' => $this->whenLoaded('cliente', function () {
                $c = $this->cliente;
                if (!$c) {
                    return null;
                }
                $completo = trim(($c->nombres ?? '') . ' ' . ($c->apellidos ?? ''));

                return $completo !== '' ? $completo : $c->razon_social;
            }),
            'fechaEmision' => $this->fecha_emision?->format('Y-m-d'),
            'subtotal' => (float) $this->subtotal,
            'iva' => (float) $this->iva,
            'descuento' => (float) $this->descuento,
            'total' => (float) $this->total,
            'estado' => $this->estado?->value ?? $this->estado,
            'estadoLabel' => $this->estado?->label(),
            'observaciones' => $this->observaciones,
            'detalles' => $this->whenLoaded('detalles', fn () => $this->detalles->map(fn ($d) => [
                'id' => $d->id,
                'descripcion' => $d->descripcion,
                'tipo' => $d->tipo,
                'referenciaId' => $d->referencia_id,
                'cantidad' => $d->cantidad,
                'precioUnitario' => (float) $d->precio_unitario,
                'subtotal' => (float) $d->subtotal,
            ])),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
