<?php

namespace App\Services\GameEngine;

use App\Models\CoinTransaction;
use App\Models\Game;
use App\Repositories\GameStateRepository;
use App\Services\CoinService;
use App\Services\MatchService;
use Illuminate\Support\Facades\Redis;
use RuntimeException;

class RealtimeGameManager
{
    public function __construct(
        private GameEngine $engine,
        private GameStateRepository $repo,
        private CoinService $coins,
        private MatchService $matchService,
    ) {}

    protected function getGame(int $gameId): Game
    {
        return Game::findOrFail($gameId);
    }

    protected function getOrCreateState(Game $game): array
    {
        $state = $this->discardStateIfPlayersChanged($game, $this->repo->load($game->id));

        if ($state === null && in_array($game->status, ['Ended', 'Interrupted', 'Cancelled'], true)) {
            $state = $this->buildEndedStateFromGame($game);
            $this->repo->save($game->id, $state);
        }

        if ($state === null) {
            $state = $this->bootstrapNewState($game);
        }
        
        if ($this->normalizeLobbyState($game, $state)) {
            $this->repo->save($game->id, $state);
        }
        if (
            ($state['status'] ?? null) === GameEngine::STATUS_PLAYING &&
            !empty($state['currentTurn']) &&
            empty($state['turnStartedAt'])
        ) {
            $state['turnStartedAt'] = $this->nowMs();
            $this->repo->save($game->id, $state);
        }
        if (empty($state['turnDurationMs'])) {
            $state['turnDurationMs'] = max(20000, (int) config('constants.turn_timer_ms', 20000));
            $this->repo->save($game->id, $state);
        }

        return $this->applyTurnTimeoutIfNeeded($game, $state);
    }

    private function discardStateIfPlayersChanged(Game $game, ?array $state): ?array
    {
        if ($state === null) {
            return null;
        }

        $status = $state['status'] ?? null;
        if (!in_array($status, [
            GameEngine::STATUS_WAITING_PLAYERS,
            GameEngine::STATUS_WAITING_READY,
            GameEngine::STATUS_PENDING,
        ], true)) {
            return $state;
        }

        $p1 = $state['player1']['id'] ?? null;
        $p2 = $state['player2']['id'] ?? null;
        if ($p1 === $game->player1_user_id && $p2 === $game->player2_user_id) {
            return $state;
        }

        $this->repo->delete($game->id);

        return null;
    }

    public function joinGame(int $gameId, int $userId): void
    {
        $game  = $this->getGame($gameId);
        $state = $this->getOrCreateState($game);

        if ($userId !== $state['player1']['id'] && $userId !== $state['player2']['id']) {
            throw new RuntimeException('User does not belong to this game');
        }

        $this->broadcastState($game, $state);
    }

    public function publicState(int $gameId, int $userId): array
    {
        $game  = $this->getGame($gameId);
        $state = $this->getOrCreateState($game);

        $game->loadMissing('player1:id,nickname,photo_avatar_filename', 'player2:id,nickname,photo_avatar_filename');
        $playerMeta = [
            'players' => [
                $game->player1_user_id => [
                    'nickname' => $game->player1?->nickname,
                    'photo_avatar_filename' => $game->player1?->photo_avatar_filename,
                ],
                $game->player2_user_id => [
                    'nickname' => $game->player2?->nickname,
                    'photo_avatar_filename' => $game->player2?->photo_avatar_filename,
                ],
            ],
        ];

        return $this->engine->publicStateFor($state, $userId, [
            ...$this->matchMeta($game),
            ...$playerMeta,
        ]);
    }

    public function markReady(int $gameId, int $userId): void
    {        
        $game  = $this->getGame($gameId);
        $state = $this->getOrCreateState($game);

        if ($userId !== $state['player1']['id'] && $userId !== $state['player2']['id']) {
            throw new RuntimeException('User does not belong to this game');
        }

        $status = $state['status'] ?? GameEngine::STATUS_PENDING;
        if ($status === GameEngine::STATUS_WAITING_PLAYERS) {
            throw new RuntimeException('Aguardando o adversário entrar no jogo.');
        }

        $state['readyPlayers'] = array_values(array_unique(array_merge($state['readyPlayers'] ?? [], [$userId])));

        $this->repo->save($game->id, $state);
        $this->persistReadyMeta($game, $state['readyPlayers'] ?? []);
        
        // Broadcast ready state ANTES de verificar se deve começar o jogo
        $this->broadcastReadyState($game, $userId, true);

        if (count($state['readyPlayers']) >= 2 && in_array($status, [
            GameEngine::STATUS_WAITING_READY,
            GameEngine::STATUS_PENDING,
        ], true)) {
            try {
                if ($game->match_id) {
                    $this->debitMatchStakeIfNeeded($game);
                } else {
                    $this->debitGameEntryFeeIfNeeded($game);
                }
            } catch (\Throwable) {
                $this->cancelLobbyForInsufficientBalance($game);
                throw new RuntimeException('Saldo insuficiente para iniciar a partida.');
            }
            $state['status'] = GameEngine::STATUS_PLAYING;
            $state['turnStartedAt'] = $state['turnStartedAt'] ?? $this->nowMs();
            $shouldSaveGame = false;
            if ($game->status !== 'Playing') {
                $game->status = 'Playing';
                $shouldSaveGame = true;
            }
            if (!$game->began_at) {
                $game->began_at = now();
                $shouldSaveGame = true;
            }
            if ($shouldSaveGame) {
                $game->save();
            }
            if ($game->match) {
                if ($game->match->status === 'Pending') {
                    $game->match->status   = 'Playing';
                    $game->match->began_at = $game->match->began_at ?? now();
                } elseif (!$game->match->began_at && $game->match->status === 'Playing') {
                    $game->match->began_at = now();
                }
                // Limpar o transition_modal_game_id e ready_players quando o próximo jogo começa
                $matchCustom = $game->match->custom ?? [];
                if (isset($matchCustom['transition_modal_game_id'])) {
                    unset($matchCustom['transition_modal_game_id']);
                }
                // Limpar ready_players quando o jogo começa
                $matchCustom['ready_players'] = [];
                $game->match->custom = $matchCustom ?: null;
                $game->match->save();
            }
            
            $this->repo->save($game->id, $state);
        }

        $this->broadcastState($game, $state);
    }

    public function markUnready(int $gameId, int $userId): void
    {
        $game  = $this->getGame($gameId);
        $state = $this->getOrCreateState($game);

        $status = $state['status'] ?? GameEngine::STATUS_PENDING;
        if ($status === GameEngine::STATUS_WAITING_PLAYERS) {
            throw new RuntimeException('Aguardando o adversário entrar no jogo.');
        }

        if (!in_array($status, [GameEngine::STATUS_WAITING_READY, GameEngine::STATUS_PENDING], true)) {
            throw new RuntimeException('Não é possível ficar "não pronto" depois da partida começar.');
        }

        if ($userId !== $state['player1']['id'] && $userId !== $state['player2']['id']) {
            throw new RuntimeException('User does not belong to this game');
        }

        $state['readyPlayers'] = array_values(array_filter(
            $state['readyPlayers'] ?? [],
            fn ($id) => (int) $id !== $userId
        ));

        $this->repo->save($game->id, $state);
        $this->persistReadyMeta($game, $state['readyPlayers'] ?? []);
        
        $this->broadcastReadyState($game, $userId, false);
        $this->broadcastState($game, $state);
    }

    public function leaveLobby(int $gameId, int $userId, string $reason = 'leave'): void
    {
        $game  = $this->getGame($gameId);
        $state = $this->repo->load($gameId);
        $match = $game->match_id ? $game->match : null;
        $participants = array_values(array_filter([
            $game->player1_user_id,
            $game->player2_user_id,
        ]));
        $logEntry = $this->buildLobbyLogEntry($game, $userId, $reason);

        if ($state && !in_array(($state['status'] ?? null), [
            GameEngine::STATUS_WAITING_PLAYERS,
            GameEngine::STATUS_WAITING_READY,
            GameEngine::STATUS_PENDING,
        ], true)) {
            return;
        }

        if (!$state && $game->status !== 'Pending') {
            return;
        }

        $isP1 = $userId === $game->player1_user_id;
        $isP2 = $userId === $game->player2_user_id;
        if (!$isP1 && !$isP2) {
            return;
        }

        if ($state) {
            $state['readyPlayers'] = array_values(array_filter(
                $state['readyPlayers'] ?? [],
                fn ($id) => (int) $id !== $userId
            ));

            $state['status'] = GameEngine::STATUS_WAITING_PLAYERS;
            $state['readyPlayers'] = [];
            $state['turnStartedAt'] = null;
        }

        if ($isP1) {
            $custom = $game->custom ?? [];
            $custom['waiting_for_opponent'] = true;
            if (!$game->match_id) {
                $custom['ready_players'] = [];
            }
            $game->custom = $custom ?: null;
            $game->status = 'Pending';
            $game->began_at = null;
            $game->player2_user_id = $game->player1_user_id;
            $game->save();

            $this->persistReadyMeta($game, []);
            $this->repo->delete($gameId);
            $state = null;
            if ($match) {
                $matchCustom = $match->custom ?? [];
                $matchCustom['waiting_for_opponent'] = true;
                $matchCustom['waiting_since'] = now()->toIso8601String();
                $match->custom = $matchCustom;
                $match->status = 'Pending';
                $match->began_at = null;
                $match->player2_user_id = $match->player1_user_id;
                $match->save();
            }

            $this->appendLobbyLogEntry($game, $logEntry);
            Redis::publish('laravel_to_ws', json_encode([
                'type'   => 'lobby_reset',
                'roomId' => $gameId,
                'userId' => $userId,
                'userIds' => $participants,
            ]));
            return;
        }

        if ($isP2 && $game->player2_user_id !== $game->player1_user_id) {
            $custom = $game->custom ?? [];
            $custom['waiting_for_opponent'] = true;
            if (!$game->match_id) {
                $custom['ready_players'] = [];
            }
            $game->custom = $custom;
            $game->player2_user_id = $game->player1_user_id;
            $game->status = 'Pending';
            $game->began_at = null;
            $game->save();

            $this->persistReadyMeta($game, []);
            $this->repo->delete($gameId);
            $state = null;
            if ($match) {
                $matchCustom = $match->custom ?? [];
                $matchCustom['waiting_for_opponent'] = true;
                $matchCustom['waiting_since'] = now()->toIso8601String();
                $match->custom = $matchCustom;
                $match->status = 'Pending';
                $match->began_at = null;
                $match->player2_user_id = $match->player1_user_id;
                $match->save();
            }

            $this->appendLobbyLogEntry($game, $logEntry);
            Redis::publish('laravel_to_ws', json_encode([
                'type'   => 'lobby_reset',
                'roomId' => $gameId,
                'userId' => $userId,
                'userIds' => $participants,
            ]));
            return;
        }

        if ($state) {
            $this->repo->save($gameId, $state);
            $this->persistReadyMeta($game, $state['readyPlayers'] ?? []);
            $this->broadcastState($game, $state);
        } else {
            $this->persistReadyMeta($game, []);
        }

        $this->appendLobbyLogEntry($game, $logEntry);
    }

    public function playCard(int $gameId, int $userId, int $cardIndex): void
    {
        $game  = $this->getGame($gameId);
        $state = $this->getOrCreateState($game);

        $status = $state['status'] ?? null;
        if ($status !== GameEngine::STATUS_PLAYING) {
            if ($status === GameEngine::STATUS_ENDED && ($state['forfeitReason'] ?? null) === 'timeout') {
                throw new RuntimeException('Tempo esgotado. O jogo terminou.');
            }
            $message = $status === GameEngine::STATUS_WAITING_PLAYERS
                ? 'Aguardando o adversário entrar no jogo.'
                : 'Aguardando confirmação dos jogadores.';
            throw new RuntimeException($message);
        }

        if (count($state['readyPlayers'] ?? []) < 2) {
            throw new RuntimeException('Aguardando confirmação dos jogadores.');
        }

        $state = $this->engine->playCard($state, $userId, $cardIndex);

        if (!empty($state['roundFinished'])) {
            $snapshot = $this->captureRoundSnapshot($state);
            $state = $this->engine->resolveRound($state);
            $this->persistRound($game, $snapshot, $state);
        }

        $this->repo->save($gameId, $state);

        if ($state['status'] === GameEngine::STATUS_ENDED) {
            $this->matchService->finalizeGame($game, $state);
        }

        $this->broadcastState($game, $state);
    }

    public function resign(int $gameId, int $userId): void
    {
        $game  = $this->getGame($gameId);
        $state = $this->getOrCreateState($game);

        $state = $this->engine->forfeit($state, $userId, 'resign');

        $this->repo->save($gameId, $state);

        $this->matchService->finalizeGame($game, $state);

        $this->broadcastState($game, $state);
    }

    protected function bootstrapNewState(Game $game): array
    {
        if (!$game->player1_user_id || !$game->player2_user_id) {
            throw new RuntimeException('Game does not have 2 players yet');
        }

        $state = $this->engine->createNewGameState(
            $game->type,
            $game->player1_user_id,
            $game->player2_user_id
        );

        $waitingForOpponent = ($game->custom['waiting_for_opponent'] ?? false) === true
            && $game->player2_user_id === $game->player1_user_id;
        $state['status'] = $waitingForOpponent
            ? GameEngine::STATUS_WAITING_PLAYERS
            : GameEngine::STATUS_WAITING_READY;
        $state['turnStartedAt'] = null;
        $state['readyPlayers'] = [];

        $this->repo->save($game->id, $state);

        $game->status   = 'Pending';
        $game->began_at = $game->began_at ?? null;

        $custom = $game->custom ?? [];
        $custom['rounds'] = $custom['rounds'] ?? [];
        if (!$game->match_id) {
            $custom['ready_players'] = [];
        }
        $game->custom = $custom;

        $game->save();

        $this->persistReadyMeta($game, []);

        return $state;
    }

    private function normalizeLobbyState(Game $game, array &$state): bool
    {
        $readyCount = count($state['readyPlayers'] ?? []);
        $status = $state['status'] ?? null;
        $waitingForOpponent = ($game->custom['waiting_for_opponent'] ?? false) === true
            && $game->player2_user_id === $game->player1_user_id;
        $expectedStatus = $waitingForOpponent
            ? GameEngine::STATUS_WAITING_PLAYERS
            : GameEngine::STATUS_WAITING_READY;

        if (in_array($status, [
            GameEngine::STATUS_WAITING_PLAYERS,
            GameEngine::STATUS_WAITING_READY,
            GameEngine::STATUS_PENDING,
        ], true)) {
            if ($status === $expectedStatus) {
                return false;
            }
            $state['status'] = $expectedStatus;
            $state['turnStartedAt'] = null;
            return true;
        }

        if ($status !== GameEngine::STATUS_PLAYING || $readyCount >= 2) {
            return false;
        }

        $state['status'] = $expectedStatus;
        $state['turnStartedAt'] = null;

        return true;
    }

    protected function captureRoundSnapshot(array $state): array
    {
        return [
            'round'        => $state['round'] ?? null,
            'leadTurn'     => $state['leadTurn'] ?? null,
            'player1Card'  => $state['player1']['playedCard'] ?? null,
            'player2Card'  => $state['player2']['playedCard'] ?? null,
            'trumpSuit'    => $state['trumpSuit'] ?? ($state['trumpCard']['naipe'] ?? null),
        ];
    }

    protected function persistRound(Game $game, array $snapshot, array $resolvedState): void
    {
        $custom = $game->custom ?? [];
        if (!array_key_exists('trump_card', $custom)) {
            $custom['trump_card'] = $resolvedState['trumpCard'] ?? null;
        }

        $winnerKey = $resolvedState['roundWinner'] ?? null;
        $leadKey = $snapshot['leadTurn'] ?? null;
        $leadPlayerId = $leadKey
            ? ($resolvedState[$leadKey]['id'] ?? null)
            : null;

        $player1CardPoints = $this->engine->totalCardPoints($snapshot['player1Card'] ?? null);
        $player2CardPoints = $this->engine->totalCardPoints($snapshot['player2Card'] ?? null);
        $player1Total = $resolvedState['player1']['score'] ?? null;
        $player2Total = $resolvedState['player2']['score'] ?? null;

        $rounds = $game->custom['rounds'] ?? [];
        $rounds[] = [
            'round_number'   => $snapshot['round'] ?? null,
            'lead_player_id' => $leadPlayerId,
            'winner_user_id' => $winnerKey ? ($resolvedState[$winnerKey]['id'] ?? null) : null,
            'player1_card'   => $snapshot['player1Card'] ?? null,
            'player2_card'   => $snapshot['player2Card'] ?? null,
            'player1_card_points' => $player1CardPoints,
            'player2_card_points' => $player2CardPoints,
            'player1_total_points' => $player1Total,
            'player2_total_points' => $player2Total,
            'trump_suit'     => $snapshot['trumpSuit'] ?? null,
            'points_awarded' => $this->engine->totalCardPoints(
                $snapshot['player1Card'] ?? null,
                $snapshot['player2Card'] ?? null
            ),
            'final_phase'    => $resolvedState['finalPhase'] ?? false,
            'timestamp'      => now()->toISOString(),
        ];

        $custom['rounds'] = $rounds;
        $game->custom = $custom;
        $game->save();
    }

    protected function broadcastState(Game $game, array $state): void
    {
        $p1Id = $state['player1']['id'];
        $p2Id = $state['player2']['id'];

        $game->loadMissing('player1:id,nickname,photo_avatar_filename', 'player2:id,nickname,photo_avatar_filename');
        $matchMeta = $this->matchMeta($game);
        $playerMeta = [
            'players' => [
                $game->player1_user_id => [
                    'nickname' => $game->player1?->nickname,
                    'photo_avatar_filename' => $game->player1?->photo_avatar_filename,
                ],
                $game->player2_user_id => [
                    'nickname' => $game->player2?->nickname,
                    'photo_avatar_filename' => $game->player2?->photo_avatar_filename,
                ],
            ],
        ];

        $userStates = [
            $p1Id => $this->engine->publicStateFor($state, $p1Id, [
                ...$matchMeta,
                ...$playerMeta,
            ]),
            $p2Id => $this->engine->publicStateFor($state, $p2Id, [
                ...$matchMeta,
                ...$playerMeta,
            ]),
        ];

        $payload = [
            'type'       => 'state_update',
            'roomId'     => $game->id,
            'userStates' => $userStates,
        ];

        Redis::publish('laravel_to_ws', json_encode($payload));
    }

    protected function broadcastReadyState(Game $game, int $userId, bool $ready): void
    {
        $base = [
            'type'    => 'ready_state',
            'userId'  => $userId,
            'ready'   => $ready,
            'gameId'  => $game->id,
            'matchId' => $game->match_id,
        ];

        if ($game->match_id) {
            Redis::publish('laravel_to_ws', json_encode([
                ...$base,
                'roomId' => $game->match_id,
            ]));
        }

        Redis::publish('laravel_to_ws', json_encode([
            ...$base,
            'roomId' => $game->id,
        ]));
    }

    private function matchMeta(Game $game): array
    {
        if (!$game->match_id) {
            return [];
        }

        $match = $game->match;
        $gameNumber = $match
            ? $match->games()->where('id', '<=', $game->id)->count()
            : null;

        return [
            'matchId'         => $game->match_id,
            'matchGameNumber' => $gameNumber,
            'sessionMode'     => 'match',
        ];
    }

    protected function debitGameEntryFeeIfNeeded(Game $game): void
    {
        if ($game->match_id) {
            return;
        }

        $custom = $game->custom ?? [];
        if (!empty($custom['fee_debited'])) {
            return;
        }

        $game->loadMissing('player1', 'player2');
        $this->coins->ensureBalance($game->player1, 2);
        $this->coins->ensureBalance($game->player2, 2);
        $this->coins->debitGameFee($game, $game->player1);
        $this->coins->debitGameFee($game, $game->player2);

        $custom['fee_debited'] = true;
        $game->custom = $custom;
        $game->save();
    }

    protected function debitMatchStakeIfNeeded(Game $game): void
    {
        if (!$game->match_id) {
            return;
        }

        $match = $game->match;
        if (!$match) {
            return;
        }

        $custom = $match->custom ?? [];
        if (!empty($custom['stake_debited'])) {
            return;
        }

        $hasDebits = CoinTransaction::where('match_id', $match->id)
            ->where('coins', '<', 0)
            ->exists();
        if ($hasDebits) {
            $custom['stake_debited'] = true;
            $match->custom = $custom;
            $match->save();
            return;
        }

        $match->loadMissing('player1', 'player2');
        $this->coins->ensureBalance($match->player1, $match->stake);
        $this->coins->ensureBalance($match->player2, $match->stake);

        $this->coins->debitMatchStake($match, $match->player1);
        $this->coins->debitMatchStake($match, $match->player2);

        $custom['stake_debited'] = true;
        $match->custom = $custom;
        $match->save();
    }

    private function cancelLobbyForInsufficientBalance(Game $game): void
    {
        $custom = $game->custom ?? [];
        $custom['cancelled_at'] = now()->toIso8601String();
        $custom['cancel_reason'] = 'insufficient_balance';
        $game->status = 'Interrupted';
        $game->custom = $custom;
        $game->began_at = null;
        $game->save();
        $this->repo->delete($game->id);

        Redis::publish('laravel_to_ws', json_encode([
            'type'   => 'lobby_reset',
            'roomId' => $game->id,
            'reason' => 'insufficient_balance',
        ]));

        if ($game->match_id) {
            $match = $game->match;
            if ($match) {
                $matchCustom = $match->custom ?? [];
                $matchCustom['cancelled_at'] = now()->toIso8601String();
                $matchCustom['cancel_reason'] = 'insufficient_balance';
                $match->status = 'Interrupted';
                $match->custom = $matchCustom;
                $match->began_at = null;
                $match->save();
            }

            Redis::publish('laravel_to_ws', json_encode([
                'type'   => 'lobby_reset',
                'roomId' => $game->match_id,
                'reason' => 'insufficient_balance',
            ]));
        }
    }

    protected function persistReadyMeta(Game $game, array $readyPlayers): void
    {
        $payload = array_values(array_unique(array_map(
            fn ($id) => (int) $id,
            $readyPlayers
        )));

        if ($game->match_id) {
            $match = $game->match;
            if (!$match) {
                return;
            }
            $custom = $match->custom ?? [];
            $custom['ready_players'] = $payload;
            $match->custom = $custom;
            $match->save();
            return;
        }

        $custom = $game->custom ?? [];
        $custom['ready_players'] = $payload;
        $game->custom = $custom;
        $game->save();
    }

    public function appendLobbyLog(int $gameId, int $userId, string $action): void
    {
        $game = $this->getGame($gameId);
        $entry = $this->buildLobbyLogEntry($game, $userId, $action);
        $this->appendLobbyLogEntry($game, $entry);
    }

    private function buildLobbyLogEntry(Game $game, int $userId, string $action): array
    {
        $game->loadMissing('player1:id,nickname', 'player2:id,nickname');
        $nickname = null;
        if ($game->player1_user_id === $userId) {
            $nickname = $game->player1?->nickname;
        } elseif ($game->player2_user_id === $userId) {
            $nickname = $game->player2?->nickname;
        }

        return [
            'action' => $action,
            'userId' => $userId,
            'nickname' => $nickname,
            'at' => now()->toIso8601String(),
        ];
    }

    private function appendLobbyLogEntry(Game $game, array $entry): void
    {
        $custom = $game->custom ?? [];
        $log = $custom['lobby_log'] ?? [];
        if (!is_array($log)) {
            $log = [];
        }
        $log[] = $entry;
        if (count($log) > 50) {
            $log = array_slice($log, -50);
        }
        $custom['lobby_log'] = $log;
        $game->custom = $custom;
        $game->save();

        $this->broadcastLobbyLogEntry($game, $entry);
    }

    public function broadcastLobbyLogEntry(Game $game, array $entry): void
    {
        Redis::publish('laravel_to_ws', json_encode([
            'type' => 'lobby_log_append',
            'roomId' => $game->id,
            'entry' => $entry,
            'userIds' => array_values(array_unique(array_filter([
                $game->player1_user_id,
                $game->player2_user_id,
            ]))),
        ]));
    }

    private function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    private function applyTurnTimeoutIfNeeded(Game $game, array $state): array
    {
        if (($state['status'] ?? null) !== GameEngine::STATUS_PLAYING) {
            return $state;
        }

        $turnKey = $state['currentTurn'] ?? null;
        $startedAt = $state['turnStartedAt'] ?? null;
        $durationMs = $state['turnDurationMs'] ?? null;
        if (!$turnKey || !$startedAt || !$durationMs) {
            return $state;
        }

        $elapsed = $this->nowMs() - (int) $startedAt;
        if ($elapsed < (int) $durationMs) {
            return $state;
        }

        $loserId = $state[$turnKey]['id'] ?? null;
        if (!$loserId) {
            return $state;
        }

        $state = $this->engine->forfeit($state, $loserId, 'timeout');
        $this->repo->save($game->id, $state);
        $this->matchService->finalizeGame($game, $state);
        $this->broadcastState($game, $state);

        return $state;
    }

    private function buildEndedStateFromGame(Game $game): array
    {
        $custom = $game->custom ?? [];
        $marksAwarded = $custom['marks_awarded'] ?? [];

        $p1Id = (int) $game->player1_user_id;
        $p2Id = (int) $game->player2_user_id;

        $winnerKey = null;
        if ($game->winner_user_id) {
            $winnerKey = $game->winner_user_id === $p1Id ? 'player1' : 'player2';
        } elseif (!empty($marksAwarded['is_draw'])) {
            $winnerKey = null;
        }

        $readyPlayers = array_values(array_filter(
            $custom['ready_players'] ?? [],
            fn ($id) => is_numeric($id)
        ));

        $forfeitReason = $custom['forfeit_reason'] ?? null;
        $forfeitLoser = $custom['forfeit_loser'] ?? null;
        $forfeitLoserKey = null;
        if (is_numeric($forfeitLoser)) {
            $forfeitLoserKey = (int) $forfeitLoser === $p1Id ? 'player1' : 'player2';
        } elseif (in_array($forfeitLoser, ['player1', 'player2'], true)) {
            $forfeitLoserKey = $forfeitLoser;
        }

        return [
            'status'    => GameEngine::STATUS_ENDED,
            'type'      => $game->type,
            'round'     => $custom['last_round'] ?? null,
            'dealer'    => $custom['last_dealer'] ?? null,
            'currentTurn' => null,
            'turnStartedAt' => null,
            'turnDurationMs' => null,
            'finalPhase' => true,
            'trumpCard'  => $custom['trump_card'] ?? null,
            'trumpSuit'  => ($custom['trump_card']['naipe'] ?? null),
            'deck'       => [],
            'roundWinner'=> null,
            'lastPlayedCards' => null,
            'matchForfeited' => !empty($forfeitReason),
            'forfeitReason'  => $forfeitReason,
            'forfeitLoser'   => $forfeitLoserKey,
            'is_draw'   => (bool) $game->is_draw,
            'winner'    => $winnerKey,
            'player1'   => [
                'id'    => $p1Id,
                'score' => (int) ($game->player1_points ?? 0),
                'marks' => (int) ($marksAwarded['player1'] ?? 0),
                'hand'  => [],
                'playedCard' => null,
            ],
            'player2'   => [
                'id'    => $p2Id,
                'score' => (int) ($game->player2_points ?? 0),
                'marks' => (int) ($marksAwarded['player2'] ?? 0),
                'hand'  => [],
                'playedCard' => null,
            ],
            'readyPlayers' => $readyPlayers,
            'custom' => $custom,
        ];
    }
}
