<?php

namespace App\Notifications\Auth\ResetPassword;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class QueueResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $resetUrl = $this->frontendBaseUrl() .
            '/reset-password' .
            '?token=' . urlencode($this->token) .
            '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Pedido de redefinição da palavra-passe')
            ->line('Recebemos um pedido para redefinir a tua palavra-passe.')
            ->action('Redefinir palavra-passe', $resetUrl)
            ->line('Se não fizeste este pedido, ignora este email.');
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
