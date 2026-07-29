<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;

class OrdenRevisionFinalizada extends Notification
{
    use Queueable;

    public function __construct(
        public OrdenTrabajoEloquentModel $orden,
    ) {
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof \Illuminate\Notifications\AnonymousNotifiable) {
            return ['mail'];
        }

        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $placa = $this->orden->vehiculo?->placa ?? 'N/D';
        $url = url('/portal/mis-ordenes/' . $this->orden->id);

        return (new MailMessage)
            ->subject("AUTOFIX IA — Revisión finalizada · {$this->orden->numero}")
            ->greeting('Hola')
            ->line("La revisión de tu vehículo ({$placa}) ya fue finalizada.")
            ->line("Orden: {$this->orden->numero}")
            ->line('Puedes ver el reporte IA, las observaciones del mecánico y el avance en tu portal.')
            ->action('Ver mi orden', $url)
            ->salutation('AUTOFIX IA');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'orden_finalizada',
            'orden_id' => $this->orden->id,
            'orden_numero' => $this->orden->numero,
            'vehiculo_placa' => $this->orden->vehiculo?->placa,
            'mensaje' => "La revisión de tu vehículo ya fue finalizada (orden {$this->orden->numero}).",
            'url' => '/portal/mis-ordenes/' . $this->orden->id,
        ];
    }
}
