<?php

namespace App\Notifications\Auth\VerifyEmail;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class QueueVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    protected function verificationUrl($notifiable): string
    {
        $verificationHash = sha1($notifiable->getEmailForVerification());

        $temporarySignedUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => $verificationHash,
            ]
        );

        $frontendBaseUrl = $this->frontendBaseUrl();

        $query = parse_url($temporarySignedUrl, PHP_URL_QUERY);

        return $frontendBaseUrl .
            '/verifyEmail' .
            '?id=' . $notifiable->getKey() .
            '&hash=' . $verificationHash .
            ($query ? '&' . $query : '');
    }

    protected function frontendBaseUrl(): string
    {
        $rawBase = config('app.frontend_url') ?? env('FRONTEND_URL') ?? 'http://localhost:5173';

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

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirmação de email')
            ->line('Obrigado por te registares na Bisca Game Platform.')
            ->line('Confirma o teu endereço de email para começares a jogar.')
            ->action('Confirmar email', $verificationUrl)
            ->line('Se não criaste esta conta, ignora este email.');
    }
}
