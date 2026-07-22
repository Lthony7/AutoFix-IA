<?php

namespace App\Services;

use App\Enums\OrdenEstado;
use App\Notifications\OrdenEstadoActualizado;
use Illuminate\Support\Facades\Notification;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;

class OrdenEstadoNotifier
{
    public function notifyIfChanged(OrdenTrabajoEloquentModel $orden, string|OrdenEstado|null $estadoAnterior): void
    {
        $orden->loadMissing(['cliente', 'vehiculo']);

        $prev = $estadoAnterior instanceof OrdenEstado
            ? $estadoAnterior->value
            : (string) $estadoAnterior;

        $nuevo = $orden->estado instanceof OrdenEstado
            ? $orden->estado->value
            : (string) $orden->estado;

        if ($prev === '' || $prev === $nuevo) {
            return;
        }

        $email = $orden->cliente?->email;
        if (!$email) {
            return;
        }

        $prevLabel = OrdenEstado::tryFrom($prev)?->label() ?? $prev;
        $nuevoLabel = OrdenEstado::tryFrom($nuevo)?->label() ?? $nuevo;

        Notification::route('mail', $email)
            ->notify(new OrdenEstadoActualizado($orden, $prevLabel, $nuevoLabel));
    }
}
