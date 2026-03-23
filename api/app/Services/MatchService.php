<?php

namespace App\Services;

use App\Models\CoinTransaction;
use App\Models\Game;
use App\Models\MatchModel;
use App\Models\User;
use App\Services\CoinService;
use App\Services\GameEngine\GameEngine;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MatchService
{
    public function __construct(
        private CoinService $coins,
        private GameEngine $engine
    ) {}

    public function createMatch(User $p1, User $p2, string $type, int $stake): MatchModel
    {
        $this->assertTypeAndStake($type, $stake);

        return $this->retryMatchCreation(function () use ($p1, $p2, $type, $stake) {
            return DB::transaction(function () use ($p1, $p2, $type, $stake) {
                $this->coins->ensureBalance($p1, $stake);
                $this->coins->ensureBalance($p2, $stake);

                $match = MatchModel::create([
                    'type'             => $type,
                    'player1_user_id'  => $p1->id,
                    'player2_user_id'  => $p2->id,
                    'stake'            => $stake,
                    'status'           => 'Pending',
                    'player1_marks'    => 0,
                    'player2_marks'    => 0,
                    'player1_points'   => 0,
                    'player2_points'   => 0,
                    'began_at'         => null,
                    'custom'           => [
                        'stake_debited' => false,
                        'lobby_log'     => [
                            $this->makeLobbyLogEntry($p1, 'create'),
                            $this->makeLobbyLogEntry($p2, 'join'),
                        ],
                    ],
                ]);

                $this->startNewGameInsideMatch($match);

                return $match;
            });
        });
    }

    public function createOpenMatch(User $p1, string $type, int $stake): MatchModel
    {
        $this->assertTypeAndStake($type, $stake);

        return $this->retryMatchCreation(function () use ($p1, $type, $stake) {
            return DB::transaction(function () use ($p1, $type, $stake) {
                $this->coins->ensureBalance($p1, $stake);

                return MatchModel::create([
                    'type'             => $type,
                    'player1_user_id'  => $p1->id,
                    'player2_user_id'  => $p1->id,
                    'stake'            => $stake,
                    'status'           => 'Pending',
                    'player1_marks'    => 0,
                    'player2_marks'    => 0,
                    'player1_points'   => 0,
                    'player2_points'   => 0,
                    'custom'           => [
                        'waiting_for_opponent' => true,
                        'waiting_since'        => now()->toIso8601String(),
                        'stake_debited'        => false,
                        'lobby_log'            => [
                            $this->makeLobbyLogEntry($p1, 'create'),
                        ],
                    ],
                ]);
            });
        });
    }

    public function joinOpenMatch(MatchModel $match, User $p2): array
    {
        $this->assertJoinableOpenMatch($match, $p2);

        $match->loadMissing('player1');
        $p1 = $match->player1;

        return DB::transaction(function () use ($match, $p1, $p2) {
            $this->coins->ensureBalance($p1, $match->stake);
            $this->coins->ensureBalance($p2, $match->stake);

            $match->player2_user_id = $p2->id;
            $match->began_at        = null;
            $custom                 = $match->custom ?? [];
            $lobbyLog = $custom['lobby_log'] ?? [];
            if (!is_array($lobbyLog)) {
                $lobbyLog = [];
            }
            $lobbyLog[] = $this->makeLobbyLogEntry($p2, 'join');
            $custom['lobby_log'] = $lobbyLog;
            unset($custom['waiting_for_opponent'], $custom['waiting_since']);
            $match->custom = $custom ?: null;
            $match->save();

            $waitingGame = $match->games()
                ->where('status', 'Pending')
                ->whereColumn('player2_user_id', 'player1_user_id')
                ->latest('id')
                ->first();

            if ($waitingGame) {
                $gameCustom = $waitingGame->custom ?? [];
                $gameLog = $gameCustom['lobby_log'] ?? [];
                if (!is_array($gameLog)) {
                    $gameLog = [];
                }
                $gameLog[] = $this->makeLobbyLogEntry($p2, 'join');
                $gameCustom['lobby_log'] = $gameLog;
                unset($gameCustom['waiting_for_opponent'], $gameCustom['waiting_since']);
                $waitingGame->player2_user_id = $p2->id;
                $waitingGame->began_at = null;
                $waitingGame->status = 'Pending';
                $waitingGame->custom = $gameCustom ?: null;
                $waitingGame->save();

                $firstGame = $waitingGame;
            } else {
                $firstGame = $this->startNewGameInsideMatch($match);
            }

            return [$match->fresh(), $firstGame];
        });
    }

    public function cancelOpenMatch(MatchModel $match): MatchModel
    {
        $waiting = ($match->custom['waiting_for_opponent'] ?? false) === true;

        if ($match->status !== 'Pending' || !$waiting) {
            throw new RuntimeException("Match já não pode ser cancelado.");
        }

        return DB::transaction(function () use ($match) {
            $custom = $match->custom ?? [];
            unset($custom['waiting_for_opponent'], $custom['waiting_since']);
            $custom['cancelled_at'] = now()->toIso8601String();

            $match->status = 'Interrupted';
            $match->custom = $custom ?: null;
            $match->save();

            $match->games()->update([
                'status' => 'Interrupted',
                'custom' => DB::raw("jsonb_set(COALESCE(custom::jsonb, '{}'::jsonb), '{waiting_for_opponent}', 'false'::jsonb) - 'waiting_for_opponent'")
            ]);

            return $match->fresh();
        });
    }

    public function cancelMatch(MatchModel $match): void
    {
        if (!in_array($match->status, ['Pending', 'Playing'], true)) {
            throw new \RuntimeException('Apenas matches pendentes podem ser cancelados.');
        }

        if ($match->status === 'Playing') {
            $pendingGame = $match->games()
                ->whereIn('status', ['Pending', 'WaitingPlayers'])
                ->latest('id')
                ->first();

            if (!$pendingGame) {
                throw new \RuntimeException('Match já começou e não pode ser cancelado.');
            }
        }

        DB::transaction(function () use ($match) {
            $matchModel = MatchModel::findOrFail($match->id);

            $custom = $matchModel->custom ?? [];
            $stakeDebited = !empty($custom['stake_debited'])
                || CoinTransaction::where('match_id', $matchModel->id)->where('coins', '<', 0)->exists();

            if ($stakeDebited) {
                $refundedIds = [];
                foreach ([$matchModel->player1, $matchModel->player2] as $player) {
                    if ($player && !in_array($player->id, $refundedIds, true)) {
                        $this->coins->refundMatchStake($matchModel, $player);
                        $refundedIds[] = $player->id;
                    }
                }
            }

            $custom['cancelled_at'] = now()->toIso8601String();
            $matchModel->custom = $custom ?: null;
            $matchModel->status = 'Interrupted';
            $matchModel->save();

            $matchModel->games()
                ->whereIn('status', ['Pending', 'WaitingPlayers'])
                ->update(['status' => 'Interrupted']);
        });
    }

    public function startNewGameInsideMatch(MatchModel $match): Game
    {
        if ($match->status === 'Ended') {
            throw new RuntimeException("Match has already ended");
        }

        if ($match->player1_marks >= 4 || $match->player2_marks >= 4) {
            throw new RuntimeException("Match marks already decided");
        }

        if ($match->status === 'Pending') {
            $match->status = 'Playing';
            $match->save();
        }

        $match->loadMissing('player1:id,nickname', 'player2:id,nickname');
        $lobbyLog = [];
        $matchHasGames = $match->games()->exists();
        $custom = $match->custom ?? [];
        if (!$matchHasGames && isset($custom['lobby_log']) && is_array($custom['lobby_log'])) {
            $lobbyLog = $custom['lobby_log'];
        } else {
            if ($match->player1) {
                $action = $match->player2_user_id === $match->player1_user_id ? 'create' : 'join';
                $lobbyLog[] = $this->makeLobbyLogEntry($match->player1, $action);
            }
            if ($match->player2 && $match->player2_user_id !== $match->player1_user_id) {
                $lobbyLog[] = $this->makeLobbyLogEntry($match->player2, 'join');
            }
        }

        $custom['ready_players'] = [];
        // Guardar qual jogo terminado deve mostrar o modal de transição
        // Buscar o último jogo terminado antes de criar o novo
        $lastEndedGame = $match->games()
            ->where('status', 'Ended')
            ->latest('id')
            ->first();
        if ($lastEndedGame) {
            $custom['transition_modal_game_id'] = $lastEndedGame->id;
        }
        $match->custom = $custom;
        $match->save();

        return $this->retryGameCreation(function () use ($match, $lobbyLog) {
            return Game::create([
                'type'             => $match->type,
                'player1_user_id'  => $match->player1_user_id,
                'player2_user_id'  => $match->player2_user_id,
                'match_id'         => $match->id,
                'status'           => 'Pending',
                'began_at'         => null,
                'custom'           => $lobbyLog ? ['lobby_log' => $lobbyLog] : null,
            ]);
        });
    }

    public function finalizeGame(Game $game, array $state): void
    {
        $p1Points = $state['player1']['score'];
        $p2Points = $state['player2']['score'];

        $result = $this->engine->calculateMarks($p1Points, $p2Points);

        $isDraw  = $result['is_draw'];
        $p1Marks = $result['p1'];
        $p2Marks = $result['p2'];

        $game->player1_points = $p1Points;
        $game->player2_points = $p2Points;
        $game->is_draw        = $isDraw;
        $game->status         = 'Ended';
        $game->ended_at       = now();
        $custom = $game->custom ?? [];
        $custom['marks_awarded'] = [
            'player1' => $p1Marks,
            'player2' => $p2Marks,
            'is_draw' => $isDraw,
        ];
        if (!empty($state['matchForfeited'])) {
            $custom['forfeit_reason'] = $state['forfeitReason'] ?? null;
            $custom['forfeit_loser'] = $state['forfeitLoser'] ?? null;
        }
        $game->custom = $custom;

        if (!$isDraw) {
            $winnerKey  = $p1Points > $p2Points ? 'player1' : 'player2';
            $winnerId   = $winnerKey === 'player1' ? $game->player1_user_id : $game->player2_user_id;
            $loserId    = $winnerKey === 'player1' ? $game->player2_user_id : $game->player1_user_id;
            $game->winner_user_id = $winnerId;
            $game->loser_user_id  = $loserId;
        }

        $game->total_time = $game->began_at ? $game->began_at->diffInSeconds(now()) : null;
        $game->save();

        if (!$game->match_id) {
            $this->handleStandaloneGameCoins($game, $p1Points, $p2Points);
            return;
        }

        $match = $game->match;

        if (!empty($state['matchForfeited'])) {
            $winnerKey = $state['winner'] ?? null;
            if (!$winnerKey && !empty($state['forfeitLoser'])) {
                $winnerKey = $state['forfeitLoser'] === 'player1' ? 'player2' : 'player1';
            }

            $winnerId = $winnerKey === 'player1' ? $match->player1_user_id : $match->player2_user_id;
            $loserId = $winnerId === $match->player1_user_id
                ? $match->player2_user_id
                : $match->player1_user_id;

            $match->player1_marks = $winnerId === $match->player1_user_id ? 4 : 0;
            $match->player2_marks = $winnerId === $match->player2_user_id ? 4 : 0;

            $match->player1_points += $p1Points;
            $match->player2_points += $p2Points;

            $match->winner_user_id = $winnerId;
            $match->loser_user_id = $loserId;
            $matchCustom = $match->custom ?? [];
            $matchCustom['forfeit_reason'] = $state['forfeitReason'] ?? null;
            $matchCustom['forfeit_loser'] = $state['forfeitLoser'] ?? null;
            $match->custom = $matchCustom;

            $this->finalizeMatch($match);
            return;
        }

        $match->player1_marks += $p1Marks;
        $match->player2_marks += $p2Marks;

        $match->player1_points += $p1Points;
        $match->player2_points += $p2Points;

        if ($match->player1_marks >= 4 || $match->player2_marks >= 4) {
            $this->finalizeMatch($match);
        } else {
            $match->save();
        }
    }

    public function finalizeMatch(MatchModel $match): void
    {
        if ($match->status === 'Ended') {
            return;
        }

        $match->status   = 'Ended';
        $match->ended_at = now();
        $match->total_time = $match->began_at ? $match->began_at->diffInSeconds(now()) : null;

        if ($match->player1_marks === $match->player2_marks) {
            // match empatado? pelas regras não está totalmente especificado,
            // vamos assumir que empate devolve stake a ambos e sem winner.
            $match->winner_user_id = null;
            $match->loser_user_id  = null;
            $match->save();

            $p1 = $match->player1;
            $p2 = $match->player2;

            $this->coins->refundMatchStake($match, $p1);
            $this->coins->refundMatchStake($match, $p2);

            return;
        }

        $winnerId = $match->player1_marks > $match->player2_marks
            ? $match->player1_user_id
            : $match->player2_user_id;

        $loserId = $winnerId === $match->player1_user_id
            ? $match->player2_user_id
            : $match->player1_user_id;

        $match->winner_user_id = $winnerId;
        $match->loser_user_id  = $loserId;
        $match->save();

        // payout do match:
        // cada jogador pagou stake → pote total = stake * 2
        // plataforma fica com 1 coin → winner recebe stake*2 - 1
        $totalPot = $match->stake * 2;
        $payout   = $totalPot - 1;

        $winner = $match->winner;

        $this->coins->creditMatchWin($match, $winner, $payout);
    }

    public function activeGameForMatch(MatchModel $match): ?Game
    {
        return $match->games()
            ->whereIn('status', ['Pending', 'Playing'])
            ->latest('id')
            ->first();
    }

    private function assertTypeAndStake(string $type, int $stake): void
    {
        if (!in_array($type, [GameEngine::TYPE_BISCA_3, GameEngine::TYPE_BISCA_9], true)) {
            throw new RuntimeException("Invalid match type");
        }

        if ($stake < 3 || $stake > 100) {
            throw new RuntimeException("Stake must be between 3 and 100");
        }
    }

    private function assertJoinableOpenMatch(MatchModel $match, User $p2): void
    {
        $waiting = ($match->custom['waiting_for_opponent'] ?? false) === true;

        if ($match->status !== 'Pending' || !$waiting) {
            throw new RuntimeException("Match indisponível.");
        }

        if ($match->player1_user_id === $p2->id) {
            throw new RuntimeException("Já estás nesta partida.");
        }
    }

    protected function handleStandaloneGameCoins(Game $game, int $p1Points, int $p2Points): void
    {
        $p1 = $game->player1;
        $p2 = $game->player2;

        if ($p1Points === $p2Points) {
            $this->coins->refundGameDraw($game, $p1);
            $this->coins->refundGameDraw($game, $p2);
            return;
        }

        $winner  = $p1Points > $p2Points ? $p1 : $p2;
        $winnerPoints = max($p1Points, $p2Points);

        // payout:
        // >=61 => 3 coins
        // >=91 => 4 coins
        // 120  => 6 coins
        $reward = 0;
        if ($winnerPoints >= 120) {
            $reward = 6;
        } elseif ($winnerPoints >= 91) {
            $reward = 4;
        } elseif ($winnerPoints >= 61) {
            $reward = 3;
        }

        if ($reward > 0) {
            $this->coins->creditGameWin($game, $winner, $reward);
        }
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

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        return $exception->getCode() === '23505' &&
            str_contains($exception->getMessage(), 'matches_pkey');
    }

    private function retryMatchCreation(Closure $callback)
    {
        try {
            return $callback();
        } catch (QueryException $e) {
            if (!$this->isDuplicateKeyException($e)) {
                throw $e;
            }

            $this->syncMatchSequence();

            return $callback();
        }
    }

    private function syncMatchSequence(): void
    {
        DB::statement("SELECT setval('matches_id_seq', COALESCE((SELECT MAX(id) FROM matches), 0))");
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
