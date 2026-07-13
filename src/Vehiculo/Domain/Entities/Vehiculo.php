<?php

namespace Src\Vehiculo\Domain\Entities;

use DateTimeImmutable;

class Vehiculo
{
    public function __construct(
        private string $id,
        private string $clienteId,
        private string $placa,
        private string $marca,
        private string $modelo,
        private int $anio,
        private ?string $color,
        private int $kilometraje,
        private string $tipoCombustible,
        private ?string $observaciones,
        private bool $activo,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?string $clienteNombre = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'clienteId' => $this->clienteId,
            'clienteNombre' => $this->clienteNombre,
            'placa' => $this->placa,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'anio' => $this->anio,
            'color' => $this->color,
            'kilometraje' => $this->kilometraje,
            'tipoCombustible' => $this->tipoCombustible,
            'observaciones' => $this->observaciones,
            'activo' => $this->activo,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
