<?php

namespace App\Notifications\PlayerUserProfile\DeleteAccount;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QueueAccountDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Conta eliminada com sucesso')
            ->line('A sua conta foi eliminada com sucesso.')
            ->line('Se não reconhece esta ação, contacte o suporte imediatamente.');
    }
}
