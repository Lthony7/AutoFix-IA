<?php

namespace Src\Vehiculo\Infrastructure\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $clienteNombre = null;
        if ($this->relationLoaded('cliente') && $this->cliente) {
            $completo = trim(($this->cliente->nombres ?? '') . ' ' . ($this->cliente->apellidos ?? ''));
            $clienteNombre = $completo !== '' ? $completo : $this->cliente->razon_social;
        }

        return [
            'id' => $this->id,
            'clienteId' => $this->cliente_id,
            'clienteNombre' => $clienteNombre,
            'placa' => $this->placa,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'anio' => (int) $this->anio,
            'color' => $this->color,
            'kilometraje' => (int) $this->kilometraje,
            'tipoCombustible' => $this->tipo_combustible,
            'observaciones' => $this->observaciones,
            'activo' => (bool) $this->activo,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
