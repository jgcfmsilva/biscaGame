<?php

namespace App\Notifications\PlayerUserProfile\DeleteAccount;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QueueAccountDeletionConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @deprecated Mantido para compatibilidade com jobs já em fila.
     */
    public string $signedUrl = '';

    public function __construct(private readonly string $frontendUrl)
    {
        $this->signedUrl = $frontendUrl;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $targetUrl = $this->frontendUrl ?: $this->signedUrl;

        return (new MailMessage)
            ->subject('Confirmar eliminação de conta')
            ->line('Recebemos um pedido para eliminar a sua conta.')
            ->line('Se não fez este pedido, ignore este email.')
            ->action('Confirmar eliminação', $targetUrl)
            ->line('Este link expira em 60 minutos e será necessário introduzir a tua palavra-passe para concluir o processo.');
    }
}
