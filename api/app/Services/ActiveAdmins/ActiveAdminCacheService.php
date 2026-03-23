<?php

namespace App\Services\ActiveAdmins;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ActiveAdminCacheService
{
    public function markLogin(User $user): void
    {
        if ($user->type !== 'A') {
            return;
        }

        $ttlMinutes = $this->ttlMinutes();

        // Evita registar admin já marcado como ativo
        if (Redis::sismember('admin_active_ids', $user->id)) {
            Log::info('Admin already active in cache, skipping re-register.', ['admin_id' => $user->id]);
            return;
        }

        Cache::put(
            "admin_active:{$user->id}",
            [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'nickname' => $user->nickname,
                'photo_avatar_filename' => $user->photo_avatar_filename,
                'logged_in_at' => now()->toIso8601String(),
            ],
            now()->addMinutes($ttlMinutes)
        );

        Redis::sadd('admin_active_ids', $user->id);
    }

    public function markLogout(User $user): void
    {
        if ($user->type !== 'A') {
            return;
        }

        Cache::forget("admin_active:{$user->id}");
        Redis::srem('admin_active_ids', $user->id);

        $ttlMinutes = $this->ttlMinutes();

        Cache::put(
            "admin_last_logout:{$user->id}",
            [
                'id' => $user->id,
                'email' => $user->email,
                'logged_out_at' => now()->toIso8601String(),
            ],
            now()->addMinutes($ttlMinutes)
        );
    }

    public function listActive(): array
    {
        $ids = Redis::smembers('admin_active_ids') ?? [];

        if (empty($ids)) {
            return [];
        }

        $keys = array_map(fn($id) => "admin_active:{$id}", $ids);
        $cachedAdmins = Cache::many($keys);

        $active = [];

        foreach ($ids as $id) {
            $key = "admin_active:{$id}";
            $data = $cachedAdmins[$key] ?? null;

            if ($data === null) {
                Redis::srem('admin_active_ids', $id);
                continue;
            }

            $active[] = $data;
        }

        return $active;
    }

    private function ttlMinutes(): int
    {
        return (int) config('session.lifetime', 120);
    }
}
