<?php

namespace App\Services\Transactions;

use App\Models\CoinTransaction;
use App\Models\CoinTransactionType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Regista uma transação de CRÉDITO (Aumenta saldo)
     */
    public function credit(User $user, string $typeName, int $amount, ?int $gameId = null, ?int $matchId = null): CoinTransaction
    {
        return $this->createTransaction($user, $typeName, abs($amount), $gameId, $matchId);
    }

    /**
     * Regista uma transação de DÉBITO (Diminui saldo)
     * Retorna null se não tiver saldo suficiente.
     */
    public function debit(User $user, string $typeName, int $amount, ?int $gameId = null, ?int $matchId = null): ?CoinTransaction
    {
        if ($user->coins_balance < abs($amount)) {
            return null; // Saldo insuficiente
        }

        // O valor no débito deve ser negativo na tabela? O enunciado diz:
        // "Positive value = credit; negative = debit" [cite: 348]
        return $this->createTransaction($user, $typeName, -abs($amount), $gameId, $matchId);
    }

    private function createTransaction(User $user, string $typeName, int $signedAmount, ?int $gameId, ?int $matchId): CoinTransaction
    {
        return DB::transaction(function () use ($user, $typeName, $signedAmount, $gameId, $matchId) {
            // 1. Buscar o ID do tipo pelo nome (ex: 'Bonus', 'Game fee')
            $type = CoinTransactionType::where('name', $typeName)->firstOrFail();

            // 2. Criar Transação
            $transaction = new CoinTransaction();
            $transaction->user_id = $user->id;
            $transaction->transaction_datetime = now();
            $transaction->coin_transaction_type_id = $type->id;
            $transaction->coins = $signedAmount; // Positivo ou Negativo
            $transaction->game_id = $gameId;
            $transaction->match_id = $matchId;
            $transaction->save();

            // 3. Atualizar Saldo do User
            $user->coins_balance += $signedAmount;
            $user->save();

            return $transaction;
        });
    }
}