<?php

namespace App\Services;

use App\Models\CoinTransaction;
use App\Models\CoinTransactionType;
use App\Models\User;
use App\Models\MatchModel;
use App\Models\Game;
use App\Notifications\Coins\CoinPurchasedNotification;
use App\Notifications\Coins\CoinSpentNotification;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use RuntimeException;

class CoinService
{
    protected static bool $transactionTypeSequenceAligned = false;

    public function changeBalance(User $user, int $coins, string $transactionTypeName, array $context = []): CoinTransaction
    {
        $type = $this->resolveTransactionType($transactionTypeName, $coins >= 0 ? 'C' : 'D');

        return DB::transaction(function () use ($user, $coins, $context, $type) {
            $transaction = CoinTransaction::create([
                'transaction_datetime'       => Carbon::now(),
                'user_id'                    => $user->id,
                'match_id'                   => $context['match_id'] ?? null,
                'game_id'                    => $context['game_id'] ?? null,
                'coin_transaction_type_id'   => $type->id,
                'coins'                      => $coins,
                'custom'                     => $context['custom'] ?? null,
            ]);

            $user->coins_balance += $coins;
            $user->save();
            $this->publishBalanceUpdate($user);

            if ($coins > 0 && $type->name === 'Coin purchase') {
                $user->notify(new CoinPurchasedNotification(
                    euros: (float) ($context['custom']['euros'] ?? 0),
                    coins: $coins,
                    paymentType: strtoupper($context['custom']['payment_type'] ?? 'DESCONHECIDO'),
                    newBalance: (int) $user->coins_balance
                ));
            } elseif ($coins < 0) {
                $user->notify(new CoinSpentNotification(
                    amountSpent: $coins,
                    newBalance: (int) $user->coins_balance,
                    context: $context['custom'] ?? $context['reason'] ?? null
                ));
            }

            return $transaction;
        });
    }

    protected function resolveTransactionType(string $name, string $direction)
    {
        $existing = CoinTransactionType::where('name', $name)->first();
        if ($existing) {
            return $existing;
        }

        if (!self::$transactionTypeSequenceAligned) {
            $this->syncCoinTransactionSequence();
            self::$transactionTypeSequenceAligned = true;
        }

        try {
            return CoinTransactionType::create([
                'name' => $name,
                'type' => $direction,
            ]);
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23505') {
                $this->syncCoinTransactionSequence();
                $retry = CoinTransactionType::where('name', $name)->first();
                if ($retry) {
                    return $retry;
                }
                return CoinTransactionType::create([
                    'name' => $name,
                    'type' => $direction,
                ]);
            }
            throw $e;
        }
    }

    protected function syncCoinTransactionSequence(): void
    {
        DB::statement("SELECT setval('coin_transaction_types_id_seq', COALESCE((SELECT MAX(id) FROM coin_transaction_types), 0))");
    }

    public function ensureBalance(User $user, int $amount): void
    {
        if ($user->coins_balance < $amount) {
            throw new RuntimeException("Não tens moedas suficientes!");
        }
    }

    public function debitMatchStake(MatchModel $match, User $user): void
    {
        $this->changeBalance(
            $user,
            -$match->stake,
            "Match stake",
            [
                'match_id' => $match->id,
                'custom'   => "Stake debit for match {$match->id}",
            ]
        );
    }

    public function refundMatchStake(MatchModel $match, User $user): void
    {
        $this->changeBalance(
            $user,
            +$match->stake,
            "match.stake.refund",
            [
                'match_id' => $match->id,
                'custom'   => "Refund stake for match {$match->id}",
            ]
        );
    }

    public function creditMatchWin(MatchModel $match, User $winner, int $amount): void
    {
        $this->changeBalance(
            $winner,
            +$amount,
            "match.win.payout",
            [
                'match_id' => $match->id,
                'custom'   => "Match {$match->id} payout to winner",
            ]
        );
    }

    public function debitGameFee(Game $game, User $user): void
    {
        // entrada: 2 coins por jogador
        $this->changeBalance(
            $user,
            -2,
            "game.fee.debit",
            [
                'game_id' => $game->id,
                'custom'  => "Game {$game->id} entry fee",
            ]
        );
    }

    public function refundGameDraw(Game $game, User $user): void
    {
        // devolve 1 coin em jogo empatado
        $this->changeBalance(
            $user,
            +1,
            "game.draw.refund",
            [
                'game_id' => $game->id,
                'custom'  => "Game {$game->id} draw refund",
            ]
        );
    }

    public function creditGameWin(Game $game, User $winner, int $amount): void
    {
        $this->changeBalance(
            $winner,
            +$amount,
            "game.win.reward",
            [
                'game_id' => $game->id,
                'custom'  => "Game {$game->id} win reward",
            ]
        );
    }

    public function applyWelcomeBonusIfMissing(User $user, int $amount = 10, string $label = 'Welcome bonus'): bool
    {
        return DB::transaction(function () use ($user, $amount, $label) {
            $type = CoinTransactionType::firstOrCreate(
                ['name' => $label],
                ['type' => 'C']
            );

            $exists = CoinTransaction::where('user_id', $user->id)
                ->where('coin_transaction_type_id', $type->id)
                ->exists();

            if ($exists) {
                return false;
            }

            $this->changeBalance($user, $amount, $label);
            return true;
        });
    }

    public function debitMatchFee(MatchModel $match, User $user): void
    {
        $amount = $match->stake;

        if ($user->coins_balance < $amount) {
            throw new RuntimeException("Saldo insuficiente para pagar o stake do match.");
        }

        $this->changeBalance(
            $user,
            -$amount,
            "match.fee.debit",
            [
                'match_id' => $match->id,
                'custom'   => "Taxa de match debitada para o match {$match->id}",
            ]
        );
    }

    protected function publishBalanceUpdate(User $user): void
    {
        Redis::publish('laravel_to_ws', json_encode([
            'type' => 'balance_update',
            'userId' => $user->id,
            'coins_balance' => $user->coins_balance,
        ]));
    }
}
