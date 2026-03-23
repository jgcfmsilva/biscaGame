<?php

namespace App\Notifications\Admin\UserInfo;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QueueUserBlockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Conta bloqueada')
            ->line('A sua conta foi bloqueada por um administrador.')
            ->line('Se acha que isto foi um erro, contacte o suporte.');
    }
}
