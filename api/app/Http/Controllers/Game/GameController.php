<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\User;
use App\Services\CoinService;
use App\Services\GameService;
use App\Services\MatchService;
use App\Services\GameEngine\RealtimeGameManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use RuntimeException;

class GameController extends Controller
{
    public function __construct(
        private GameService $gameService,
        private RealtimeGameManager $realtime,
        private MatchService $matchService,
        private CoinService $coins,
    ) {}

    public function createGame(Request $request)
    {
        $this->assertPlayer($request);

        $data = $request->validate([
            'type'        => 'required|in:3,9',
            'opponent_id' => 'required|exists:users,id',
        ]);

        $p1 = $request->user();
        $p2 = User::findOrFail($data['opponent_id']);

        if ($p2->blocked) {
            return response()->json(['message' => 'Adversário bloqueado'], 403);
        }

        try {
            $this->coins->ensureBalance($p1, 2);
            $this->coins->ensureBalance($p2, 2);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $res = $this->gameService->createMultiplayerGame(
            type: $data['type'],
            p1:   $p1,
            p2:   $p2
        );

        return response()->json($res, 201);
    }

    public function createQuickGame(Request $request)
    {
        $this->assertPlayer($request);

        $data = $request->validate([
            'type' => 'required|in:3,9',
        ]);

        $user = $request->user();
        try {
            $this->coins->ensureBalance($user, 2);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $pending = $this->gameService->findActiveLobbyForUser($user);

        if ($pending) {
            return response()->json([
                'message' => 'Já tens um lobby a aguardar adversário.',
                'game'    => $pending,
            ], 409);
        }

        $game = $this->gameService->createOpenGame($user, $data['type']);

        $this->publishLobbyActiveUpdate();

        return response()->json([
            'game' => $game,
        ], 201);
    }

    public function myPending(Request $request)
    {
        $this->assertPlayer($request);

        $user = $request->user();

        $pending = $this->gameService->findActiveLobbyForUser($user);

        if (!$pending) {
            return response()->json(['game' => null]);
        }

        return response()->json(['game' => $pending]);
    }

    public function show(Request $request, Game $game)
    {
        $this->assertPlayer($request);

        $user = $request->user();

        if ($user->id !== $game->player1_user_id && $user->id !== $game->player2_user_id) {
            return response()->json(['message' => 'Não fazes parte deste jogo'], 403);
        }

        $game->load([
            'player1:id,nickname,photo_avatar_filename',
            'player2:id,nickname,photo_avatar_filename',
            'winner:id,nickname',
            'loser:id,nickname',
            'match.player1:id,nickname,photo_avatar_filename',
            'match.player2:id,nickname,photo_avatar_filename',
            'match.winner:id,nickname',
            'match.loser:id,nickname',
        ]);

        return response()->json([
            'game' => $game,
        ]);
    }

    public function state(Request $request, Game $game)
    {
        $this->assertPlayer($request);

        $user = $request->user();

        if ($user->id !== $game->player1_user_id && $user->id !== $game->player2_user_id) {
            return response()->json(['message' => 'Não fazes parte deste jogo'], 403);
        }

        try {
            $state = $this->realtime->publicState($game->id, $user->id);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'state' => $state,
        ]);
    }

    public function join(Request $request, Game $game)
    {
        $this->assertPlayer($request);

        $user = $request->user();

        if ($game->status === 'Ended') {
            // Permitir reentrar se fizer parte do match e o match ainda não terminou
            if ($game->match_id && $game->match && $game->match->status !== 'Ended') {
                $this->realtime->joinGame($game->id, $user->id);
                return response()->json(['game' => $game]);
            }
            return response()->json(['message' => 'Jogo já terminou'], 422);
        }

        if ($game->player1_user_id === $user->id || $game->player2_user_id === $user->id) {
            $lobbyLog = $game->custom['lobby_log'] ?? [];
            if (is_array($lobbyLog) && $game->status === 'Pending') {
                $lastEntry = null;
                for ($i = count($lobbyLog) - 1; $i >= 0; $i--) {
                    $entry = $lobbyLog[$i] ?? null;
                    if (!is_array($entry)) {
                        continue;
                    }
                    if (($entry['userId'] ?? null) == $user->id) {
                        $lastEntry = $entry;
                        break;
                    }
                }
                $lastAction = $lastEntry['action'] ?? null;
                if (in_array($lastAction, ['leave', 'kick'], true)) {
                    $this->realtime->appendLobbyLog($game->id, $user->id, 'join');
                }
            }
            $this->realtime->joinGame($game->id, $user->id);
            return response()->json(['game' => $game]);
        }

        // Não permitir join direto se for parte de um match
        if ($game->match_id) {
            return response()->json(['message' => 'Deves entrar através do match'], 403);
        }

        try {
            $this->gameService->joinOpenGame($game, $user);
        } catch (RuntimeException $e) {
            $message = $e->getMessage() ?: 'Não foi possível entrar no jogo';
            return response()->json(['message' => $message], 422);
        }

        $game->refresh();

        try {
            $this->realtime->appendLobbyLog($game->id, $user->id, 'join');
            $this->realtime->joinGame($game->id, $user->id);
            $this->realtime->joinGame($game->id, $game->player1_user_id);
        } catch (\Throwable) {
            // falha de WS não deve impedir a operação; estado fica em cache
        }

        $this->publishLobbyActiveUpdate();

        return response()->json([
            'game' => $game->refresh()->load('player1', 'player2', 'match'),
        ]);
    }

    public function playCard(Request $request, int $gameId)
    {
        $this->assertPlayer($request);

        $data = $request->validate([
            'cardIndex' => 'required|integer|min:0',
        ]);

        try {
            $this->realtime->playCard(
                $gameId,
                $request->user()->id,
                $data['cardIndex']
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'ok']);
    }

    public function ready(Request $request, int $gameId)
    {
        $this->assertPlayer($request);

        try {
            $this->realtime->markReady($gameId, $request->user()->id);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'ok']);
    }

    public function unready(Request $request, int $gameId)
    {
        $this->assertPlayer($request);

        try {
            $this->realtime->markUnready($gameId, $request->user()->id);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'ok']);
    }

    public function leaveLobby(Request $request, int $gameId)
    {
        $this->assertPlayer($request);
        $user = $request->user();

        $game = Game::find($gameId);
        if ($game && $game->player1_user_id === $user->id && $game->status === 'Pending') {
            if ($game->match_id) {
                $match = $game->match;
                if ($match) {
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

                    Redis::publish('laravel_to_ws', json_encode([
                        'type'   => 'lobby_reset',
                        'roomId' => $game->id,
                        'reason' => 'cancelled',
                        'userIds' => array_values(array_filter([
                            $game->player1_user_id,
                            $game->player2_user_id,
                        ])),
                    ]));
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
            } else {
                try {
                    $this->gameService->cancelOpenGame($game, $user);
                } catch (\Throwable $e) {
                    return response()->json(['message' => $e->getMessage()], 422);
                }

                Redis::publish('laravel_to_ws', json_encode([
                    'type'   => 'lobby_reset',
                    'roomId' => $game->id,
                    'reason' => 'cancelled',
                    'userIds' => array_values(array_filter([
                        $game->player1_user_id,
                        $game->player2_user_id,
                    ])),
                ]));

                $this->publishLobbyActiveUpdate();

                return response()->json(['status' => 'ok']);
            }
        }

        try {
            $this->realtime->leaveLobby($gameId, $user->id, 'leave');
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->publishLobbyActiveUpdate();

        return response()->json(['status' => 'ok']);
    }

    public function kickLobby(Request $request, Game $game)
    {
        $this->assertPlayer($request);

        $user = $request->user();

        if ($game->player1_user_id !== $user->id) {
            return response()->json(['message' => 'Apenas o criador pode expulsar.'], 403);
        }

        $targetId = $game->player2_user_id;
        if (!$targetId || $targetId === $game->player1_user_id) {
            return response()->json(['message' => 'Não existe adversário para expulsar.'], 422);
        }

        try {
            $this->realtime->leaveLobby($game->id, $targetId, 'kick');
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        Redis::publish('laravel_to_ws', json_encode([
            'type'    => 'lobby_kicked',
            'userId'  => $targetId,
            'gameId'  => $game->id,
            'matchId' => $game->match_id,
        ]));

        return response()->json(['status' => 'ok']);
    }

    public function cancelLobby(Request $request, Game $game)
    {
        $this->assertPlayer($request);

        try {
            $cancelled = $this->gameService->cancelOpenGame($game, $request->user());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        Redis::publish('laravel_to_ws', json_encode([
            'type'   => 'lobby_reset',
            'roomId' => $game->id,
            'reason' => 'cancelled',
            'userIds' => array_values(array_filter([
                $game->player1_user_id,
                $game->player2_user_id,
            ])),
        ]));

        $this->publishLobbyActiveUpdate();

        return response()->json(['game' => $cancelled]);
    }

    public function resign(Request $request, int $gameId)
    {
        $this->assertPlayer($request);

        try {
            $this->realtime->resign(
                $gameId,
                $request->user()->id
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'ok']);
    }

    private function assertPlayer(Request $request): void
    {
        if ($request->user()?->type === 'A') {
            abort(403, 'Administradores não podem jogar.');
        }
    }

    private function publishLobbyActiveUpdate(): void
    {
        Redis::publish('laravel_to_ws', json_encode([
            'type' => 'lobby_active_update',
        ]));
    }

}
