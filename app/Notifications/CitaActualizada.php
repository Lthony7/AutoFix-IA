<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Src\Cita\Infrastructure\Models\CitaEloquentModel;

class CitaActualizada extends Notification
{
    use Queueable;

    public function __construct(
        public CitaEloquentModel $cita,
        public string $accion,
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
        $this->cita->loadMissing(['cliente', 'vehiculo']);
        $placa = $this->cita->vehiculo?->placa ?? 'N/D';
        $fecha = $this->cita->fecha_hora?->format('d/m/Y H:i') ?? '—';
        $cliente = $this->cita->cliente?->razon_social ?? 'Cliente';

        $asunto = match ($this->accion) {
            'cancelada' => "Cita cancelada · {$placa}",
            'reagendada' => "Cita reagendada · {$placa}",
            'agendada' => "Nueva cita agendada · {$placa}",
            default => "Cita actualizada · {$placa}",
        };

        $linea = match ($this->accion) {
            'cancelada' => "{$cliente} canceló la cita del vehículo {$placa} (prevista para {$fecha}).",
            'reagendada' => "{$cliente} reagendó la cita del vehículo {$placa} para {$fecha}.",
            'agendada' => "{$cliente} agendó una cita para el vehículo {$placa} el {$fecha}.",
            default => "La cita del vehículo {$placa} fue actualizada ({$fecha}).",
        };

        return (new MailMessage)
            ->subject("AUTOFIX IA — {$asunto}")
            ->greeting('Hola')
            ->line($linea)
            ->action('Ver calendario', url('/calendario'))
            ->salutation('AUTOFIX IA');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'cita_' . $this->accion,
            'cita_id' => $this->cita->id,
            'accion' => $this->accion,
            'mensaje' => match ($this->accion) {
                'cancelada' => 'Una cita fue cancelada por el cliente.',
                'reagendada' => 'Una cita fue reagendada por el cliente.',
                'agendada' => 'Un cliente agendó una nueva cita.',
                default => 'Una cita fue actualizada.',
            },
            'url' => '/calendario',
        ];
    }
}
