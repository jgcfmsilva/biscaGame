<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaymentGatewayService
{
    private string $baseUrl = 'https://dad-payments-api.vercel.app';

    public function debit(string $type, string $reference, int $value): void
    {
        $payload = [
            'type'     => $type,
            'reference'=> $reference,
            'value'    => $value,
        ];

        $response = Http::post("{$this->baseUrl}/api/debit", $payload);

        if ($response->status() === 201) {
            return; // ok
        }

        if ($response->status() === 422) {
            throw new \RuntimeException('Payment rejected: ' . json_encode($response->json()));
        }

        throw new \RuntimeException('Payment gateway error: ' . $response->status());
    }
}
