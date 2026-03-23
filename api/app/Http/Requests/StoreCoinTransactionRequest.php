<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoinTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // O utilizador logado pode fazer o pedido
    }

    public function rules(): array
    {
        // Regras baseadas na secção "VALIDATION" do enunciado [cite: 383]
        return [
            'type' => 'required|in:MBWAY,PAYPAL,IBAN,MB,VISA',
            'value' => 'required|integer|min:1|max:100', // Valor em Euros
            'reference' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');
                    // Validações específicas por tipo [cite: 387-392]
                    if ($type === 'MBWAY' && !preg_match('/^9\d{8}$/', $value)) {
                        $fail('A referência MBWAY deve ter 9 dígitos e começar por 9.');
                    }
                    if ($type === 'VISA' && !preg_match('/^4\d{15}$/', $value)) {
                        $fail('O cartão VISA deve ter 16 dígitos e começar por 4.');
                    }
                    if ($type === 'MB' && !preg_match('/^\d{5}-\d{9}$/', $value)) {
                        $fail('A referência MB deve ser no formato 12345-123456789.');
                    }
                    if ($type === 'IBAN' && !preg_match('/^[A-Z]{2}\d{23}$/', $value)) {
                        $fail('O IBAN deve começar com 2 letras seguidas de 23 dígitos.');
                    }
                    if ($type === 'PAYPAL' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail('A referência PayPal deve ser um email válido.');
                    }
                },
            ],
        ];
    }
}