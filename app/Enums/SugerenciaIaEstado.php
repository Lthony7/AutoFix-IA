<?php

namespace App\Enums;

enum SugerenciaIaEstado: string
{
    case Generada = 'generada';
    case EnRevision = 'en_revision';
    case Confirmada = 'confirmada';
    case Modificada = 'modificada';
    case Descartada = 'descartada';
    case Cerrada = 'cerrada';

    public function label(): string
    {
        return match ($this) {
            self::Generada => 'Generada',
            self::EnRevision => 'En revisión',
            self::Confirmada => 'Confirmada',
            self::Modificada => 'Modificada',
            self::Descartada => 'Descartada',
            self::Cerrada => 'Cerrada',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
