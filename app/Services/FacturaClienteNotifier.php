<?php

namespace App\Services;

use App\Notifications\FacturaEmitida;
use Illuminate\Support\Facades\Notification;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;

class FacturaClienteNotifier
{
    public function notifyEmitida(FacturaEloquentModel $factura): void
    {
        $factura->loadMissing(['cliente.user', 'ordenTrabajo.vehiculo']);

        $cliente = $factura->cliente;
        if (!$cliente instanceof ClienteEloquentModel) {
            return;
        }

        $notification = new FacturaEmitida($factura);

        if ($cliente->user) {
            $cliente->user->notify($notification);

            return;
        }

        if ($cliente->email) {
            Notification::route('mail', $cliente->email)->notify($notification);
        }
    }
}
