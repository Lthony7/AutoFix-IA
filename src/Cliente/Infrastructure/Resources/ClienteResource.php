<?php

namespace Src\Cliente\Infrastructure\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $nombreCompleto = trim(($this->nombres ?? '') . ' ' . ($this->apellidos ?? ''));

        return [
            'id' => $this->id,
            'tipoDocumento' => $this->tipo_documento,
            'numeroDocumento' => $this->numero_documento,
            'razonSocial' => $this->razon_social,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'nombreCompleto' => $nombreCompleto !== '' ? $nombreCompleto : $this->razon_social,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'estado' => (bool) $this->estado,
            'userId' => $this->user_id,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
