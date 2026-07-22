<?php

namespace App\Enums;

enum FacturaEstado: string
{
    case Borrador = 'borrador';
    case Emitida = 'emitida';
    case Pagada = 'pagada';
    case Anulada = 'anulada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Emitida => 'Emitida',
            self::Pagada => 'Pagada',
            self::Anulada => 'Anulada',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
