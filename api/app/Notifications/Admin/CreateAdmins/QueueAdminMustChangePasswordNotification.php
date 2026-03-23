<?php

namespace App\Notifications\Admin\CreateAdmins;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class QueueAdminMustChangePasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $token)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $resetUrl = $this->frontendBaseUrl() .
            '/reset-password' .
            '?token=' . urlencode($this->token) .
            '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Alteração obrigatória da palavra-passe de administrador')
            ->line('Foi criada uma conta de administrador para si. Para utilizar a conta, tem de alterar a palavra-passe.')
            ->action('Alterar palavra-passe', $resetUrl)
            ->line('O link abre a página para definir uma nova palavra-passe. Se não reconhece este pedido, contacte o suporte.');
    }

    protected function frontendBaseUrl(): string
    {
        $rawBase = config('app.frontend_url') ?? env('FRONTEND_URL') ?? URL::to('/');

        $parsed = parse_url($rawBase);

        if ($parsed && isset($parsed['scheme'], $parsed['host'])) {
            $base = $parsed['scheme'] . '://' . $parsed['host'];
            if (isset($parsed['port'])) {
                $base .= ':' . $parsed['port'];
            }

            return rtrim($base, '/');
        }

        return rtrim($rawBase, '/');
    }
}
