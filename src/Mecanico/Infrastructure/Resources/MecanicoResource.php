<?php

namespace Src\Mecanico\Infrastructure\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MecanicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'nombreCompleto' => trim($this->nombres . ' ' . $this->apellidos),
            'documento' => $this->documento,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'especialidad' => $this->especialidad,
            'horarioDisponible' => $this->horario_disponible,
            'activo' => (bool) $this->activo,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
