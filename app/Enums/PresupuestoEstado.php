<?php

namespace App\Enums;

enum PresupuestoEstado: string
{
    case Borrador = 'borrador';
    case Guardado = 'guardado';
    case VinculadoCita = 'vinculado_cita';
    case Vencido = 'vencido';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Guardado => 'Guardado',
            self::VinculadoCita => 'Vinculado a cita',
            self::Vencido => 'Vencido',
            self::Cancelado => 'Cancelado',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
