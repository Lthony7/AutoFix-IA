<?php

namespace App\Enums;

enum PagoEstado: string
{
    case Pendiente = 'pendiente';
    case Pagado = 'pagado';
    case Anulado = 'anulado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Pagado => 'Pagado',
            self::Anulado => 'Anulado',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
