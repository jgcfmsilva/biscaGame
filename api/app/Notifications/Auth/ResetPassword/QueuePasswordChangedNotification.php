<?php

namespace App\Notifications\Auth\ResetPassword;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QueuePasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Palavra-passe alterada')
            ->line('A tua palavra-passe foi alterada com sucesso.')
            ->line('Se não reconheces esta alteração, contacta o suporte imediatamente.');
    }
}
