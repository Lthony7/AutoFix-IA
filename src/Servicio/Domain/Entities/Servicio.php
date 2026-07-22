<?php

namespace Src\Servicio\Domain\Entities;

use DateTimeImmutable;

class Servicio
{
    public function __construct(
        private string $id,
        private string $nombre,
        private ?string $descripcion,
        private float $precioBase,
        private bool $activo,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precioBase' => (float) $this->precioBase,
            'activo' => $this->activo,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
