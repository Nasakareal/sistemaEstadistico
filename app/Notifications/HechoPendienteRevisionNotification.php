<?php

namespace App\Notifications;

use App\Models\Hechos;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HechoPendienteRevisionNotification extends Notification
{
    use Queueable;

    protected Hechos $hecho;

    public function __construct(Hechos $hecho)
    {
        $this->hecho = $hecho;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'hecho_id' => $this->hecho->id,
            'folio_c5i' => $this->hecho->folio_c5i,
            'fecha' => $this->hecho->fecha,
            'hora' => $this->hecho->hora,
            'tipo_hecho' => $this->hecho->tipo_hecho,
            'sector' => $this->hecho->sector,
            'unidad_org_id' => $this->hecho->unidad_org_id,
            'mensaje' => 'El hecho ' . ($this->hecho->folio_c5i ?: '#' . $this->hecho->id) . ' sigue pendiente de revisión.',
            'url' => route('hechos.show', $this->hecho->id),
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Hecho pendiente de revisión')
            ->line('El hecho ' . ($this->hecho->folio_c5i ?: '#' . $this->hecho->id) . ' sigue pendiente de revisión.')
            ->action('Ver hecho', route('hechos.show', $this->hecho->id));
    }
}
