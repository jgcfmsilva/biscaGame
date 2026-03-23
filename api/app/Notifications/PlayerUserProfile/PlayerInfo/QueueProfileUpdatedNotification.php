<?php

namespace App\Notifications\PlayerUserProfile\PlayerInfo;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QueueProfileUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Informações de perfil atualizadas')
            ->line('Os dados do seu perfil foram alterados.')
            ->line('Se não reconhece esta alteração, contacte o suporte imediatamente.');
    }
}
