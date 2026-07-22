<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;

class OrdenEstadoActualizado extends Notification
{
    use Queueable;

    public function __construct(
        public OrdenTrabajoEloquentModel $orden,
        public string $estadoAnterior,
        public string $estadoNuevo,
    ) {
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $placa = $this->orden->vehiculo?->placa ?? 'N/D';

        return (new MailMessage)
            ->subject("AUTOFIX IA — Orden {$this->orden->numero} actualizada")
            ->greeting('Hola')
            ->line("Tu orden de trabajo {$this->orden->numero} (vehículo {$placa}) cambió de estado.")
            ->line("Antes: {$this->estadoAnterior}")
            ->line("Ahora: {$this->estadoNuevo}")
            ->line('Puedes revisar el detalle en el portal de cliente.')
            ->salutation('AUTOFIX IA');
    }
}
