<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;

class GameStateRepository
{
    private const PREFIX = 'game_state:';

    public function load(int $gameId): ?array
    {
        $key = self::PREFIX.$gameId;
        $raw = Redis::get($key);

        if (!$raw) {
            return null;
        }

        $state = json_decode($raw, true);

        return is_array($state) ? $state : null;
    }

    public function save(int $gameId, array $state, int $ttlSeconds = 3600): void
    {
        $key = self::PREFIX.$gameId;
        Redis::setex($key, $ttlSeconds, json_encode($state));
    }

    public function delete(int $gameId): void
    {
        $key = self::PREFIX.$gameId;
        Redis::del($key);
    }
}
