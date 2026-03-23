<?php

namespace App\Http\Controllers\Leaderboards;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function getGlobal()
    {
        // Top 10 by Games Won
        $mostGamesWon = User::where('type', 'P')
            ->whereNull('deleted_at')
            ->withCount('gamesWon')
            ->orderByDesc('games_won_count')
            ->orderBy('nickname') // Tie-breaker
            ->take(10)
            ->get(['id', 'nickname', 'photo_avatar_filename']);

        // Top 10 by Matches Won
        $mostMatchesWon = User::where('type', 'P')
            ->whereNull('deleted_at')
            ->withCount('matchesWon')
            ->orderByDesc('matches_won_count')
            ->orderBy('nickname') // Tie-breaker
            ->take(10)
            ->get(['id', 'nickname', 'photo_avatar_filename']);

        return response()->json([
            'most_games_won' => $mostGamesWon,
            'most_matches_won' => $mostMatchesWon,
        ]);
    }

    public function getPersonal(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Count Games Won
        $gamesWon = $user->gamesWon()->count();

        // Count Matches Won
        $matchesWon = $user->matchesWon()->count();

        // Count "Bandeiras" (Clean Sweeps: Games with 120 points)
        // Check both player1 and player2 sides
        $bandeiras = Game::where(function ($query) use ($user) {
            // Player 1 with 120 points
            $query->where('player1_user_id', $user->id)
                ->where('player1_points', 120);
        })->orWhere(function ($query) use ($user) {
            // Player 2 with 120 points
            $query->where('player2_user_id', $user->id)
                ->where('player2_points', 120);
        })->count();

        // Count "Capotes" (Games with 91-119 points)
        $capotes = Game::where(function ($query) use ($user) {
            // Player 1 with >= 91 and < 120
            $query->where('player1_user_id', $user->id)
                ->where('player1_points', '>=', 91)
                ->where('player1_points', '<', 120);
        })->orWhere(function ($query) use ($user) {
            // Player 2 with >= 91 and < 120
            $query->where('player2_user_id', $user->id)
                ->where('player2_points', '>=', 91)
                ->where('player2_points', '<', 120);
        })->count();

        return response()->json([
            'id' => $user->id,
            'nickname' => $user->nickname,
            'games_won' => $gamesWon,
            'matches_won' => $matchesWon,
            'bandeiras_count' => $bandeiras,
            'capotes_count' => $capotes
        ]);
    }
}
