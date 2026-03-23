<?php

namespace App\Notifications\Coins;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoinSpentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $amountSpent,
        private readonly int $newBalance,
        private readonly array|string|null $context = null
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $lines = [
            'Gastaste ' . abs($this->amountSpent) . ' moedas.',
        ];

        $contextText = $this->normalizeContext($this->context);
        if ($contextText) {
            $lines[] = 'Motivo: ' . $contextText;
        }

        $lines[] = 'Saldo atual: ' . $this->newBalance . ' moedas.';

        $message = (new MailMessage)
            ->subject('Moedas usadas na tua conta')
            ->greeting('Olá ' . ($notifiable->nickname ?? $notifiable->name ?? 'jogador') . ' 👋');

        foreach ($lines as $line) {
            $message->line($line);
        }

        return $message
            ->line('Se não reconheces esta operação, contacta o suporte imediatamente.');
    }

    private function normalizeContext($context): ?string
    {
        if (empty($context)) {
            return null;
        }

        if (is_string($context)) {
            return $context;
        }

        if (is_array($context)) {
            $parts = [];
            foreach ($context as $key => $value) {
                if (is_scalar($value)) {
                    $parts[] = ucfirst(str_replace('_', ' ', (string) $key)) . ': ' . $value;
                }
            }
            return $parts ? implode(' | ', $parts) : null;
        }

        return null;
    }
}
