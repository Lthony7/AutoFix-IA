<?php

namespace Src\Mecanico\Domain\Entities;

use DateTimeImmutable;

class Mecanico
{
    public function __construct(
        private string $id,
        private ?string $userId,
        private string $nombres,
        private string $apellidos,
        private string $documento,
        private ?string $telefono,
        private ?string $email,
        private string $especialidad,
        private ?string $horarioDisponible,
        private bool $activo,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {
    }

    public function getNombreCompleto(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'nombreCompleto' => $this->getNombreCompleto(),
            'documento' => $this->documento,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'especialidad' => $this->especialidad,
            'horarioDisponible' => $this->horarioDisponible,
            'activo' => $this->activo,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
