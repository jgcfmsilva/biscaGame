<?php

namespace App\Services\ActivePlayers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ActivePlayerCacheService
{
    public function markLogin(User $user): void
    {
        if ($user->type !== 'P') {
            return;
        }

        $ttl = now()->addMinutes($this->ttlMinutes());

        Cache::put(
            "player_active:{$user->id}",
            [
                'id' => $user->id,
                'email' => $user->email,
                'nickname' => $user->nickname,
                'logged_in_at' => now()->toIso8601String(),
            ],
            $ttl
        );

        Redis::sadd($this->setKey(), $user->id);
        $this->broadcastCount();
    }

    public function markLogout(User $user): void
    {
        if ($user->type !== 'P') {
            return;
        }

        Cache::forget("player_active:{$user->id}");
        Redis::srem($this->setKey(), $user->id);
        $this->broadcastCount();
    }

    public function countActive(): int
    {
        $ids = Redis::smembers($this->setKey()) ?? [];
        if (empty($ids)) {
            return 0;
        }

        $keys = array_map(fn ($id) => "player_active:{$id}", $ids);
        $cached = Cache::many($keys);

        $count = 0;
        foreach ($ids as $id) {
            $key = "player_active:{$id}";
            $data = $cached[$key] ?? null;
            if ($data === null) {
                Redis::srem($this->setKey(), $id);
                continue;
            }
            $count++;
        }

        return $count;
    }

    private function broadcastCount(): void
    {
        try {
            $count = $this->countActive();
            Redis::publish($this->channelName(), json_encode([
                'type' => 'players_online',
                'count' => $count,
            ]));
        } catch (\Throwable $e) {
            Log::warning('Failed to broadcast players_online', ['error' => $e->getMessage()]);
        }
    }

    private function channelName(): string
    {
        $prefix = env('REDIS_PREFIX', '');
        return $prefix . 'laravel_to_ws';
    }

    private function setKey(): string
    {
        $prefix = config('cache.prefix') ? config('cache.prefix') . ':' : '';
        return "{$prefix}player_active_ids";
    }

    private function ttlMinutes(): int
    {
        return (int) config('session.lifetime', 120);
    }
}
