<?php

namespace App\Enums;

enum CitaTipo: string
{
    case Mantenimiento = 'mantenimiento';
    case Reparacion = 'reparacion';
    case Diagnostico = 'diagnostico';

    public function label(): string
    {
        return match ($this) {
            self::Mantenimiento => 'Mantenimiento',
            self::Reparacion => 'Reparación',
            self::Diagnostico => 'Diagnóstico',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
