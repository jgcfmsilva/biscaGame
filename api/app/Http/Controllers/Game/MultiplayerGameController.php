<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;

class MultiplayerGameController extends Controller
{
    public function create(Request $request)
    {
        $this->assertPlayer($request);

        $data = $request->validate([
            'type' => 'required|in:3,9',
        ]);

        $game = Game::create([
            'type' => $data['type'],
            'player1_user_id' => $request->user()->id,
            'player2_user_id' => null,
            'status' => 'Pending',
            'match_id' => null,
            'custom' => [
                'ready_players' => [],
            ],
        ]);

        return response()->json([
            'success' => true,
            'game_id' => $game->id
        ]);
    }

    private function assertPlayer(Request $request): void
    {
        if ($request->user()?->type === 'A') {
            abort(403, 'Administradores não podem jogar.');
        }
    }
}
