<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\User;
use App\Services\CoinService;
use App\Services\MatchService;
use App\Services\GameEngine\RealtimeGameManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use RuntimeException;

class MatchController extends Controller
{
    public function __construct(
        private MatchService $matchService,
        private RealtimeGameManager $realtime,
        private CoinService $coins,
    ) {}

    public function store(Request $request)
    {
        $this->assertPlayer($request);

        $data = $request->validate([
            'type'        => 'required|in:3,9',
            'opponent_id' => 'nullable|exists:users,id',
            'stake'       => 'required|integer|min:1',
        ]);

        $p1 = $request->user();

        // Caso seja um lobby aberto sem adversário definido
        if (empty($data['opponent_id'])) {
            try {
                $match = $this->matchService->createOpenMatch(
                    p1: $p1,
                    type: $data['type'],
                    stake: $data['stake']
                );
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            $this->publishLobbyActiveUpdate();

            return response()->json([
                'match'     => $match->load('player1', 'player2'),
                'firstGame' => null,
            ], 201);
        }

        $p2 = User::findOrFail($data['opponent_id']);

        if ($p2->blocked) {
            return response()->json(['message' => 'Adversário bloqueado'], 403);
        }

        $totalStake = $data['stake'];
        try {
            $this->coins->ensureBalance($p1, $totalStake);
            $this->coins->ensureBalance($p2, $totalStake);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $match = $this->matchService->createMatch(
            type: $data['type'],
            p1:   $p1,
            p2:   $p2,
            stake: $totalStake
        );

        $firstGame = $match->games()->first();

        return response()->json([
            'match'      => $match->load('player1', 'player2'),
            'firstGame'  => $firstGame?->load('player1', 'player2'),
        ], 201);
    }

    public function create(Request $request)
    {
        return $this->store($request);
    }

    public function show(Request $request, MatchModel $match)
    {
        $this->assertPlayer($request);

        $user = $request->user();

        if ($user->id !== $match->player1_user_id && $user->id !== $match->player2_user_id) {
            return response()->json(['message' => 'Não fazes parte deste match'], 403);
        }

        $match->loadMissing([
            'player1:id,nickname,photo_avatar_filename',
            'player2:id,nickname,photo_avatar_filename',
            'winner:id,nickname',
            'loser:id,nickname',
            'games' => function ($query) {
                $query
                    ->with([
                        'player1:id,nickname,photo_avatar_filename',
                        'player2:id,nickname,photo_avatar_filename',
                        'winner:id,nickname',
                        'loser:id,nickname',
                        'match',
                    ])
                    ->orderBy('began_at', 'asc')
                    ->orderBy('id', 'asc');
            },
        ]);

        $activeGame = $this->matchService->activeGameForMatch($match);
        $activeGame?->loadMissing([
            'player1:id,nickname,photo_avatar_filename',
            'player2:id,nickname,photo_avatar_filename',
            'winner:id,nickname',
            'loser:id,nickname',
            'match',
        ]);

        $requestedGame = null;
        if ($request->filled('game_id')) {
            $requestedGame = $match->games()
                ->where('id', $request->query('game_id'))
                ->with([
                    'player1:id,nickname,photo_avatar_filename',
                    'player2:id,nickname,photo_avatar_filename',
                    'winner:id,nickname',
                    'loser:id,nickname',
                    'match',
                ])
                ->first();
        }

        $lastGame = $match->games()
            ->with([
                'player1:id,nickname,photo_avatar_filename',
                'player2:id,nickname,photo_avatar_filename',
                'winner:id,nickname',
                'loser:id,nickname',
                'match',
            ])
            ->latest('id')
            ->first();

        return response()->json([
            'match'           => $match->loadMissing([
                'player1', 'player2', 'winner', 'loser',
            ]),
            'active_game'     => $activeGame?->load('player1', 'player2'),
            'requested_game'  => $requestedGame?->load('player1', 'player2', 'match'),
            'last_game'       => $lastGame?->load('player1', 'player2', 'match'),
        ]);
    }

    public function join(Request $request, MatchModel $match)
    {
        $this->assertPlayer($request);

        $user = $request->user();

        if ($match->status === 'Ended') {
            return response()->json(['message' => 'Match já terminou'], 422);
        }

        if ($match->player1_user_id === $user->id || $match->player2_user_id === $user->id) {
            $activeGame = $this->matchService->activeGameForMatch($match);

            if ($activeGame) {
                $this->realtime->joinGame($activeGame->id, $user->id);
            }

            return response()->json([
                'match'       => $match->load('player1', 'player2'),
                'active_game' => $activeGame?->load('player1', 'player2'),
            ]);
        }

        $waiting = $match->player2_user_id === $match->player1_user_id;
        if (!$waiting) {
            return response()->json(['message' => 'Match já tem dois jogadores'], 422);
        }

        try {
            [$match, $activeGame] = $this->matchService->joinOpenMatch($match, $user);
        } catch (RuntimeException $e) {
            $message = $e->getMessage() ?: 'Não foi possível entrar no match';
            return response()->json(['message' => $message], 422);
        }

        $match->refresh();
        if (!isset($activeGame)) {
            $activeGame = $this->matchService->activeGameForMatch($match);
        }

        if ($activeGame) {
            $this->notifyOwnerPendingReady($match, $activeGame);

            try {
                $this->realtime->joinGame($activeGame->id, $user->id);
                $this->realtime->joinGame($activeGame->id, $match->player1_user_id);
                $gameLog = $activeGame->custom['lobby_log'] ?? [];
                if (is_array($gameLog) && !empty($gameLog)) {
                    $lastEntry = $gameLog[count($gameLog) - 1] ?? null;
                    if (is_array($lastEntry)) {
                        $this->realtime->broadcastLobbyLogEntry($activeGame, $lastEntry);
                    }
                }
            } catch (\Throwable) {
                // falha de WS não deve impedir a operação
            }
        }

        $this->publishLobbyActiveUpdate();

        return response()->json([
            'match'       => $match->load('player1', 'player2'),
            'active_game' => $activeGame?->load('player1', 'player2'),
        ]);
    }

    public function resign(Request $request, MatchModel $match)
    {
        $this->assertPlayer($request);

        $user = $request->user();

        if ($match->status === 'Ended') {
            return response()->json(['message' => 'Match já terminou'], 422);
        }

        $activeGame = $match->games()
            ->whereIn('status', ['Pending', 'Playing'])
            ->latest('id')
            ->first();

        if ($activeGame) {
            try {
                $this->realtime->resign($activeGame->id, $user->id);
            } catch (\Throwable $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    public function cancel(Request $request, MatchModel $match)
    {
        $this->assertPlayer($request);

        $user = $request->user();

        if ($user->id !== $match->player1_user_id) {
            return response()->json(['message' => 'Apenas o criador pode cancelar'], 403);
        }

        $waiting = ($match->custom['waiting_for_opponent'] ?? false) === true
            && $match->player2_user_id === $match->player1_user_id;

        try {
            if ($waiting) {
                $this->matchService->cancelOpenMatch($match);
            } else {
                $this->matchService->cancelMatch($match);
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $activeGame = $this->matchService->activeGameForMatch($match);
        if ($activeGame) {
            Redis::publish('laravel_to_ws', json_encode([
                'type'   => 'lobby_reset',
                'roomId' => $activeGame->id,
                'reason' => 'cancelled',
                'userIds' => array_values(array_filter([
                    $match->player1_user_id,
                    $match->player2_user_id,
                ])),
            ]));
        }

        Redis::publish('laravel_to_ws', json_encode([
            'type'   => 'lobby_reset',
            'roomId' => $match->id,
            'reason' => 'cancelled',
            'userIds' => array_values(array_filter([
                $match->player1_user_id,
                $match->player2_user_id,
            ])),
        ]));

        $this->publishLobbyActiveUpdate();

        return response()->json(['status' => 'ok']);
    }

    public function nextGame(Request $request, MatchModel $match)
    {
        $this->assertPlayer($request);

        $user = $request->user();

        if ($user->id !== $match->player1_user_id && $user->id !== $match->player2_user_id) {
            return response()->json(['message' => 'Não fazes parte deste match'], 403);
        }

        // Apenas o criador do match (player1) pode pedir novo jogo; restantes devem esperar pelo active_game existente.
        if ($user->id !== $match->player1_user_id) {
            return response()->json(['message' => 'Só o criador pode iniciar o próximo jogo.'], 403);
        }

        if ($match->status === 'Ended') {
            return response()->json(['message' => 'Match já terminou'], 422);
        }

        try {
            $payload = DB::transaction(function () use ($match) {
                // Bloquear o match para evitar corrida entre múltiplas chamadas ao next-game
                $lockedMatch = MatchModel::whereKey($match->id)->lockForUpdate()->firstOrFail();

                $activeGame = $this->matchService->activeGameForMatch($lockedMatch);
                if ($activeGame) {
                    return [
                        'match'       => $lockedMatch->load('player1', 'player2'),
                        'active_game' => $activeGame->load('player1', 'player2'),
                    ];
                }

                $lastGame = $lockedMatch->games()->latest('id')->first();
                if ($lastGame && $lastGame->status !== 'Ended') {
                    throw new RuntimeException('Ainda há um jogo em curso.');
                }

                $activeGame = $this->matchService->startNewGameInsideMatch($lockedMatch);

                return [
                    'match'       => $lockedMatch->load('player1', 'player2'),
                    'active_game' => $activeGame->load('player1', 'player2'),
                ];
            });
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        Redis::publish('laravel_to_ws', json_encode([
            'type' => 'match_next_game',
            'matchId' => $match->id,
            'gameId' => $payload['active_game']->id,
            'userIds' => array_values(array_filter([
                $match->player1_user_id,
                $match->player2_user_id,
            ])),
        ]));

        return response()->json($payload);
    }

    private function assertPlayer(Request $request): void
    {
        if ($request->user()?->type === 'A') {
            abort(403, 'Administradores não podem jogar.');
        }
    }

    protected function notifyOwnerPendingReady(MatchModel $match, $game): void
    {
        Redis::publish('laravel_to_ws', json_encode([
            'type'     => 'pending_ready',
            'ownerId'  => $match->player1_user_id,
            'gameId'   => $game->id,
            'matchId'  => $match->id,
            'game'     => [
                'id'      => $game->id,
                'type'    => $game->type,
                'stake'   => $match->stake,
                'matchId' => $match->id,
            ],
        ]));
    }

    private function publishLobbyActiveUpdate(): void
    {
        Redis::publish('laravel_to_ws', json_encode([
            'type' => 'lobby_active_update',
        ]));
    }
}
