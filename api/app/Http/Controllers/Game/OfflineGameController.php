<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Services\GameEngine\GameEngine;
use App\Repositories\OfflineGameRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use RuntimeException;

class OfflineGameController extends Controller
{
    private const BOT_ID = 999999;

    public function __construct(
        private GameEngine $engine,
        private OfflineGameRepository $repo
    ) {}

    private function playerKey(Request $request): int|string
    {
        $user = $request->user('sanctum');

        if (!$user) {
            $user = Auth::guard('sanctum')->user();
        }

        if ($user) {
            return $user->id;
        }

        $offlineKey = $request->header('X-Offline-Player');
        if (is_string($offlineKey) && $offlineKey !== '') {
            return $offlineKey;
        }

        return crc32($request->session()->getId());
    }

    private function playerId(int|string $playerKey): int
    {
        return is_int($playerKey) ? $playerKey : 0;
    }

    public function start(Request $request)
    {
        $this->assertPlayer($request);

        $data = $request->validate([
            'type' => 'required|in:3,9',
        ]);

        $playerKey = $this->playerKey($request);
        $playerId  = $this->playerId($playerKey);

        $state = $this->engine->createNewGameState(
            $data['type'],
            $playerId,
            self::BOT_ID
        );

        // Se o bot for o primeiro a jogar, faz já a jogada inicial antes de devolver o estado
        $state = $this->engine->autoPlayForBot($state, self::BOT_ID);

        $this->repo->save($playerKey, $state);

        $publicState = $this->engine->publicStateFor($state, $playerId);

        return response()->json([
            'state' => $publicState,
        ]);
    }

    public function playCard(Request $request)
    {
        $this->assertPlayer($request);

        $data = $request->validate([
            'cardIndex' => 'required|integer|min:0',
        ]);

        $key = $this->playerKey($request);
        $state = $this->repo->load($key);

        if (!$state) {
            throw new RuntimeException('Jogo offline não iniciado');
        }

        $playerId = $this->playerId($key);

        try {
            $state = $this->engine->playCard($state, $playerId, $data['cardIndex']);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $this->repo->save($key, $state);

        return response()->json([
            'state' => $this->engine->publicStateFor($state, $playerId)
        ]);
    }

    public function reconnect(Request $request)
    {
        $playerKey = $this->playerKey($request);
        $state = $this->repo->load($playerKey);

        if (!$state) {
            return response()->json([
                'message' => 'Jogo offline não encontrado',
            ], 404);
        }

        $playerId = $this->playerId($playerKey);

        // Bot pode estar à espera para liderar; garante que joga antes de devolver
        $state = $this->engine->autoPlayForBot($state, self::BOT_ID);

        // refresh TTL
        $this->repo->save($playerKey, $state);

        return response()->json([
            'state' => $this->engine->publicStateFor($state, $playerId)
        ]);
    }

    public function resolveRound(Request $request)
    {
        $this->assertPlayer($request);

        $key = $this->playerKey($request);
        $state = $this->repo->load($key);

        if (!$state) {
            throw new RuntimeException('Jogo offline não iniciado');
        }

        $playerId = $this->playerId($key);

        $state = $this->engine->resolveRound($state);

        $this->persistOrClear($key, $state);

        return response()->json([
            'state' => $this->engine->publicStateFor($state, $playerId)
        ]);
    }

    public function resign(Request $request)
    {
        $this->assertPlayer($request);

        $data = $request->validate([
            'reason' => 'sometimes|string|in:resign,timeout',
        ]);

        $playerKey = $this->playerKey($request);
        $state = $this->repo->load($playerKey);

        if (!$state) {
            throw new RuntimeException('Offline game not started');
        }

        $playerId = $this->playerId($playerKey);

        $reason = $data['reason'] ?? 'resign';

        $state = $this->engine->forfeit($state, $playerId, $reason);

        $this->persistOrClear($playerKey, $state);

        $publicState = $this->engine->publicStateFor($state, $playerId);

        return response()->json([
            'state' => $publicState,
        ]);
    }

    private function assertPlayer(Request $request): void
    {
        $user = $request->user('sanctum') ?? Auth::guard('sanctum')->user();
        if ($user?->type === 'A') {
            abort(403, 'Administradores não podem jogar.');
        }
    }

    private function persistOrClear(int|string $key, array $state): void
    {
        $finished = ($state['status'] === GameEngine::STATUS_ENDED) || !empty($state['matchForfeited']);

        if ($finished) {
            $this->repo->delete($key);
            return;
        }

        $this->repo->save($key, $state);
    }
}
