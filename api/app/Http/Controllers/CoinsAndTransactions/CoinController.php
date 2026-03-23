<?php

namespace App\Http\Controllers\CoinsAndTransactions;

use App\Http\Controllers\Controller;
use App\Models\CoinPurchase;
use App\Services\CoinService;
use App\Services\PaymentGatewayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CoinController extends Controller
{
    public function __construct(
        private PaymentGatewayService $payments,
        private CoinService $coinService
    ) {}

    public function balance(Request $request)
    {
        return response()->json([
            'coins_balance' => $request->user()->coins_balance,
        ]);
    }

    public function transactions(Request $request)
    {
        $user = $request->user();

        $transactions = $user->transactions()
            ->with('type')
            ->orderByDesc('transaction_datetime')
            ->get();

        return response()->json($transactions);
    }

    public function buy(Request $request)
    {
        $data = $request->validate([
            'payment_type'     => 'required|in:MBWAY,PAYPAL,IBAN,MB,VISA',
            'payment_reference'=> 'required|string',
            'value'            => 'required|integer|min:1|max:99',
        ]);

        $validator = Validator::make($data, [
            'payment_reference' => [
                function ($attribute, $value, $fail) use ($data) {
                    switch ($data['payment_type']) {
                        case 'MBWAY':
                            if (!preg_match('/^9[0-9]{8}$/', $value)) {
                                $fail('Referência MBWAY inválida');
                            }
                            break;
                        case 'PAYPAL':
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $fail('Email PAYPAL inválido');
                            }
                            break;
                        case 'IBAN':
                            if (!preg_match('/^[A-Z]{2}[0-9]{23}$/', $value)) {
                                $fail('IBAN inválido');
                            }
                            break;
                        case 'MB':
                            if (!preg_match('/^[0-9]{5}-[0-9]{9}$/', $value)) {
                                $fail('Referência MB inválida');
                            }
                            break;
                        case 'VISA':
                            if (!preg_match('/^4[0-9]{15}$/', $value)) {
                                $fail('VISA inválido');
                            }
                            break;
                    }
                }
            ]
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        $euros = $data['value'];
        $coins = $euros * 10;

        $this->payments->debit($data['payment_type'], $data['payment_reference'], $euros);

        $transaction = $this->coinService->changeBalance($user, $coins, 'Coin purchase', [
            'custom' => [
                'payment_type' => $data['payment_type'],
                'payment_reference' => $data['payment_reference'],
                'euros' => $euros,
            ]
        ]);

        CoinPurchase::create([
            'purchase_datetime' => Carbon::now(),
            'user_id'           => $user->id,
            'coin_transaction_id' => $transaction->id,
            'euros'             => $euros,
            'payment_type'      => $data['payment_type'],
            'payment_reference' => $data['payment_reference'],
            'custom'            => null,
        ]);

        return response()->json([
            'message'       => 'Coins compradas com sucesso',
            'coins_balance' => $user->coins_balance,
        ], 201);
    }
}
