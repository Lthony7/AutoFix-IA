<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;

class FacturaEmitida extends Notification
{
    use Queueable;

    public function __construct(
        public FacturaEloquentModel $factura,
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
        $this->factura->loadMissing(['ordenTrabajo.vehiculo']);
        $placa = $this->factura->ordenTrabajo?->vehiculo?->placa ?? 'N/D';
        $total = number_format((float) $this->factura->total, 2);
        $url = url('/portal/facturas/' . $this->factura->id);

        return (new MailMessage)
            ->subject("AUTOFIX IA — Factura {$this->factura->numero} emitida")
            ->greeting('Hola')
            ->line("El taller emitió la factura de tu orden (vehículo {$placa}).")
            ->line("Factura: {$this->factura->numero}")
            ->line("Total: \${$total}")
            ->line('Incluye el desglose de servicios y piezas utilizadas.')
            ->action('Ver factura', $url)
            ->salutation('AUTOFIX IA');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'factura_emitida',
            'factura_id' => $this->factura->id,
            'factura_numero' => $this->factura->numero,
            'orden_id' => $this->factura->orden_trabajo_id,
            'total' => (float) $this->factura->total,
            'mensaje' => "Factura {$this->factura->numero} emitida por el taller.",
            'url' => '/portal/facturas/' . $this->factura->id,
        ];
    }
}
