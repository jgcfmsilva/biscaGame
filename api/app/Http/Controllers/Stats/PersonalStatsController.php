<?php

namespace App\Http\Controllers\Stats;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PersonalStatsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $gamesWon = $user->gamesWon()->count();
        $matchesWon = $user->matchesWon()->count();

        $bandeiras = Game::where(function ($query) use ($user) {
            $query->where('player1_user_id', $user->id)
                ->where('player1_points', 120);
        })->orWhere(function ($query) use ($user) {
            $query->where('player2_user_id', $user->id)
                ->where('player2_points', 120);
        })->count();

        $capotes = Game::where(function ($query) use ($user) {
            $query->where('player1_user_id', $user->id)
                ->where('player1_points', '>=', 91)
                ->where('player1_points', '<', 120);
        })->orWhere(function ($query) use ($user) {
            $query->where('player2_user_id', $user->id)
                ->where('player2_points', '>=', 91)
                ->where('player2_points', '<', 120);
        })->count();

        $from = Carbon::now()->subMonths(5)->startOfMonth();

        $gamesByMonth = Game::query()
            ->selectRaw("TO_CHAR(COALESCE(ended_at, began_at), 'YYYY-MM') as month, COUNT(*) as total")
            ->where(function ($query) use ($user) {
                $query->where('player1_user_id', $user->id)
                    ->orWhere('player2_user_id', $user->id);
            })
            ->where(function ($query) use ($from) {
                $query->whereNotNull('ended_at')
                    ->where('ended_at', '>=', $from)
                    ->orWhere(function ($q) use ($from) {
                        $q->whereNull('ended_at')
                            ->whereNotNull('began_at')
                            ->where('began_at', '>=', $from);
                    });
            })
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'total' => (int) $row->total,
            ])
            ->toArray();

        $totalSeconds = (float) Game::query()
            ->where(function ($query) use ($user) {
                $query->where('player1_user_id', $user->id)
                    ->orWhere('player2_user_id', $user->id);
            })
            ->whereNotNull('total_time')
            ->sum('total_time');

        $totalHours = $totalSeconds > 0 ? round($totalSeconds / 3600, 2) : 0.0;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nickname' => $user->nickname,
            ],
            'leaderboard' => [
                'games_won' => $gamesWon,
                'matches_won' => $matchesWon,
                'capotes_count' => $capotes,
                'bandeiras_count' => $bandeiras,
            ],
            'games_by_month' => $gamesByMonth,
            'time_played' => [
                'total_seconds' => $totalSeconds,
                'total_hours' => $totalHours,
            ],
        ]);
    }
}
