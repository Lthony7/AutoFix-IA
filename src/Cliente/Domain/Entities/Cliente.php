<?php

namespace Src\Cliente\Domain\Entities;

use DateTimeImmutable;

class Cliente
{
    public function __construct(
        private string $id,
        private string $tipoDocumento,
        private string $numeroDocumento,
        private string $razonSocial,
        private ?string $nombres,
        private ?string $apellidos,
        private string $direccion,
        private string $telefono,
        private string $email,
        private bool $estado,
        private ?string $userId,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTipoDocumento(): string
    {
        return $this->tipoDocumento;
    }

    public function getNumeroDocumento(): string
    {
        return $this->numeroDocumento;
    }

    public function getRazonSocial(): string
    {
        return $this->razonSocial;
    }

    public function getNombres(): ?string
    {
        return $this->nombres;
    }

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function getDireccion(): string
    {
        return $this->direccion;
    }

    public function getTelefono(): string
    {
        return $this->telefono;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function isEstado(): bool
    {
        return $this->estado;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getNombreCompleto(): string
    {
        $completo = trim(($this->nombres ?? '') . ' ' . ($this->apellidos ?? ''));

        return $completo !== '' ? $completo : $this->razonSocial;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tipoDocumento' => $this->tipoDocumento,
            'numeroDocumento' => $this->numeroDocumento,
            'razonSocial' => $this->razonSocial,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'nombreCompleto' => $this->getNombreCompleto(),
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'estado' => $this->estado,
            'userId' => $this->userId,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
