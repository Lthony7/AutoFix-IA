<?php

namespace App\Enums;

enum OrdenEstado: string
{
    case Pendiente = 'pendiente';
    case EnDiagnostico = 'en_diagnostico';
    case EnReparacion = 'en_reparacion';
    case Finalizada = 'finalizada';
    case Entregada = 'entregada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnDiagnostico => 'En diagnóstico',
            self::EnReparacion => 'En reparación',
            self::Finalizada => 'Finalizada',
            self::Entregada => 'Entregada',
            self::Cancelada => 'Cancelada',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
