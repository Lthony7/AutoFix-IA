<?php

namespace App\Services;

use App\Enums\OrdenEstado;
use App\Notifications\OrdenEstadoActualizado;
use App\Notifications\OrdenRevisionFinalizada;
use Illuminate\Support\Facades\Notification;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;

class OrdenEstadoNotifier
{
    public function notifyIfChanged(OrdenTrabajoEloquentModel $orden, string|OrdenEstado|null $estadoAnterior): void
    {
        $orden->loadMissing(['cliente.user', 'vehiculo']);

        $prev = $estadoAnterior instanceof OrdenEstado
            ? $estadoAnterior->value
            : (string) $estadoAnterior;

        $nuevo = $orden->estado instanceof OrdenEstado
            ? $orden->estado->value
            : (string) $orden->estado;

        if ($prev === '' || $prev === $nuevo) {
            return;
        }

        if ($nuevo === OrdenEstado::Finalizada->value) {
            $this->notifyCliente($orden, new OrdenRevisionFinalizada($orden));

            return;
        }

        $prevLabel = OrdenEstado::tryFrom($prev)?->label() ?? $prev;
        $nuevoLabel = OrdenEstado::tryFrom($nuevo)?->label() ?? $nuevo;

        $this->notifyCliente($orden, new OrdenEstadoActualizado($orden, $prevLabel, $nuevoLabel));
    }

    private function notifyCliente(OrdenTrabajoEloquentModel $orden, object $notification): void
    {
        $user = $orden->cliente?->user;
        if ($user) {
            $user->notify($notification);

            return;
        }

        $email = $orden->cliente?->email;
        if ($email) {
            Notification::route('mail', $email)->notify($notification);
        }
    }
}
