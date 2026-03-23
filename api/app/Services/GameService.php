<?php

namespace App\Services;

use App\Models\Game;
use App\Models\User;
use App\Repositories\GameStateRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Closure;
use RuntimeException;

class GameService
{
    public function __construct(
        private CoinService $coins,
        private GameStateRepository $stateRepo,
    ) {}

    public function createMultiplayerGame(string $type, User $p1, User $p2): array
    {
        $game = $this->createGame($type, $p1->id, $p2->id, [
            'ready_players' => [],
            'lobby_log' => [
                $this->makeLobbyLogEntry($p1, 'join'),
                $this->makeLobbyLogEntry($p2, 'join'),
            ],
        ]);

        return [
            'gameId'  => $game->id,
            'player1' => $p1->id,
            'player2' => $p2->id,
        ];
    }

    public function findPendingLobby(User $user): ?Game
    {
        return Game::where('player1_user_id', $user->id)
            ->where('match_id', null)
            ->where('status', 'Pending')
            ->whereColumn('player2_user_id', 'player1_user_id')
            ->where('custom->waiting_for_opponent', true)
            ->first();
    }

    public function findActiveLobbyForUser(User $user): ?Game
    {
        return Game::query()
            ->whereNull('match_id')
            ->where('status', 'Pending')
            ->where(function ($query) use ($user) {
                $query
                    ->where('player1_user_id', $user->id)
                    ->orWhere('player2_user_id', $user->id);
            })
            ->orderByDesc('id')
            ->first();
    }

    public function createOpenGame(User $user, string $type): Game
    {
        return $this->createGame($type, $user->id, $user->id, [
            'waiting_for_opponent' => true,
            'ready_players'        => [],
            'lobby_log'            => [
                $this->makeLobbyLogEntry($user, 'create'),
            ],
        ], null);
    }

    public function joinOpenGame(Game $game, User $user): Game
    {
        return DB::transaction(function () use ($game, $user) {
            $lockedGame = Game::whereKey($game->id)->lockForUpdate()->first();
            if (!$lockedGame) {
                throw new RuntimeException('Jogo não encontrado');
            }

            $owner = $lockedGame->player1;

            $this->coins->ensureBalance($owner, 2);
            $this->coins->ensureBalance($user, 2);

            $waitingForOpponent = ($lockedGame->custom['waiting_for_opponent'] ?? false)
                && $lockedGame->player2_user_id === $lockedGame->player1_user_id;

            if ($waitingForOpponent) {
                $lockedGame->player2_user_id = $user->id;
                $lockedGame->status          = 'Pending';
                $lockedGame->began_at        = null;
                $custom = $lockedGame->custom ?? [];
                unset($custom['waiting_for_opponent']);
                $lockedGame->custom = $custom ?: null;
            } else {
                if ($lockedGame->player2_user_id) {
                    throw new RuntimeException('Jogo já tem dois jogadores');
                }
                $lockedGame->player2_user_id = $user->id;
                $lockedGame->status          = 'Pending';
                $lockedGame->began_at        = null;
            }

            $lockedGame->save();
            $this->stateRepo->delete($lockedGame->id);

            return $lockedGame->fresh();
        });
    }

    public function cancelOpenGame(Game $game, User $user): Game
    {
        if ($game->match_id) {
            throw new RuntimeException('Jogo pertence a um match.');
        }

        if ($game->player1_user_id !== $user->id) {
            throw new RuntimeException('Apenas o criador pode cancelar.');
        }

        if ($game->status !== 'Pending') {
            throw new RuntimeException('Jogo já começou ou terminou.');
        }

        $custom = $game->custom ?? [];
        unset($custom['waiting_for_opponent'], $custom['ready_players']);
        $custom['cancelled_at'] = now()->toIso8601String();

        $game->status = 'Interrupted';
        $game->custom = $custom ?: null;
        $game->began_at = null;
        $game->save();

        $this->stateRepo->delete($game->id);

        return $game->fresh();
    }

    private function createGame(string $type, int $player1Id, int $player2Id, array $custom, ?int $matchId = null): Game
    {
        return $this->retryGameCreation(function () use ($type, $player1Id, $player2Id, $custom, $matchId) {
            return Game::create([
                'type'             => $type,
                'player1_user_id'  => $player1Id,
                'player2_user_id'  => $player2Id,
                'status'           => 'Pending',
                'match_id'         => $matchId,
                'custom'           => $custom,
            ]);
        });
    }

    private function makeLobbyLogEntry(User $user, string $action): array
    {
        return [
            'action' => $action,
            'userId' => $user->id,
            'nickname' => $user->nickname,
            'at' => now()->toIso8601String(),
        ];
    }

    private function retryGameCreation(Closure $callback)
    {
        try {
            return $callback();
        } catch (QueryException $e) {
            if (!$this->isGameDuplicateKey($e)) {
                throw $e;
            }
            $this->syncGameSequence();
            return $callback();
        }
    }

    private function isGameDuplicateKey(QueryException $e): bool
    {
        return $e->getCode() === '23505' && str_contains($e->getMessage(), 'games_pkey');
    }

    private function syncGameSequence(): void
    {
        DB::statement("SELECT setval('games_id_seq', COALESCE((SELECT MAX(id) FROM games), 0))");
    }
}
