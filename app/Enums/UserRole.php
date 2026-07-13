<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrador = 'administrador';
    case Recepcionista = 'recepcionista';
    case Mecanico = 'mecanico';
    case Cliente = 'cliente';

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Recepcionista => 'Recepcionista',
            self::Mecanico => 'Mecánico',
            self::Cliente => 'Cliente',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
