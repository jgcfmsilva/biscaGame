<?php

namespace App\Notifications\Coins;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoinPurchasedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly float $euros,
        private readonly int $coins,
        private readonly string $paymentType,
        private readonly int $newBalance
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Compra de moedas confirmada')
            ->greeting('Olá ' . ($notifiable->nickname ?? $notifiable->name ?? 'jogador') . ' 👋')
            ->line('A tua compra de moedas foi concluída com sucesso.')
            ->line('Valor pago: €' . number_format($this->euros, 2, ',', ' '))
            ->line('Moedas creditadas: ' . $this->coins)
            ->line('Método de pagamento: ' . strtoupper($this->paymentType))
            ->line('Saldo atual: ' . $this->newBalance . ' moedas')
            ->line('Obrigado por jogares na Bisca Game Platform!');
    }
}
