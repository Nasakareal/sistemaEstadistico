<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserDisconnectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public array $payload
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return $this->payload;
    }
}
