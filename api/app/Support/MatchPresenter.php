<?php

namespace App\Support;

use App\Models\MatchModel;

class MatchPresenter
{
    public static function format(MatchModel $match): array
    {
        $match->loadMissing('player1:id,nickname', 'player2:id,nickname');

        $waiting = ($match->custom['waiting_for_opponent'] ?? false) === true;

        $payload = $match->toArray();
        $payload['waiting_for_opponent'] = $waiting;

        if ($waiting && $match->player1_user_id === $match->player2_user_id) {
            $payload['player2_user_id'] = null;
            $payload['player2'] = null;
        }

        return $payload;
    }
}
