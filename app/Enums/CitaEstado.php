<?php

namespace App\Enums;

enum CitaEstado: string
{
    case Programada = 'programada';
    case Cancelada = 'cancelada';
    case Reagendada = 'reagendada';
    case Completada = 'completada';

    public function label(): string
    {
        return match ($this) {
            self::Programada => 'Programada',
            self::Cancelada => 'Cancelada',
            self::Reagendada => 'Reagendada',
            self::Completada => 'Completada',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
