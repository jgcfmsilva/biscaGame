<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;

class OfflineGameRepository
{
    private function key(string|int $id): string
    {
        return "offline_game:{$id}";
    }

    public function save(string|int $id, array $state): void
    {
        Redis::setex(
            $this->key($id),
            600, // 10 minutos
            json_encode($state)
        );
    }

    public function load(string|int $id): ?array
    {
        $raw = Redis::get($this->key($id));
        return $raw ? json_decode($raw, true) : null;
    }

    public function delete(string|int $id): void
    {
        Redis::del($this->key($id));
    }
}
