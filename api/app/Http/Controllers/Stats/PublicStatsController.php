<?php

namespace App\Http\Controllers\Stats;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Game;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PublicStatsController extends Controller
{
    public function __invoke()
    {
        // Only count games started in the last 24h as "active" to exclude potential zombies
        $activeGames = Game::whereNull('ended_at')
            ->where('began_at', '>=', now()->subDay())
            ->count();

        // Estimate online players based on token usage in last 5 mins (tighter window)
        $onlineUsers = DB::table('personal_access_tokens')
            ->where('last_used_at', '>=', now()->subMinutes(5))
            ->distinct('tokenable_id')
            ->count('tokenable_id');

        return response()->json([
            'server' => [
                'status' => 'online',
                'latency_ms' => 1
            ],
            'players_online' => $onlineUsers,
            'active_games' => $activeGames,
            'total_users' => User::count(),
            'total_games' => Game::count(),
            'games_per_month' => $this->gamesPerMonth(),
            'games_last_7_days' => $this->gamesLast7Days(),
        ]);
    }

    private function gamesPerMonth(): array
    {
        $from = Carbon::now()->subMonths(11)->startOfMonth();

        $rows = Game::query()
            ->selectRaw("TO_CHAR(COALESCE(ended_at, began_at), 'YYYY-MM') as month, COUNT(*) as total")
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
            ->keyBy('month');

        // Preenche os últimos 12 meses (inclui meses sem jogos com total 0)
        $filled = [];
        for ($i = 0; $i < 12; $i++) {
            $monthLabel = $from->copy()->addMonths($i)->format('Y-m');
            $filled[] = [
                'month' => $monthLabel,
                'total' => isset($rows[$monthLabel]) ? (int) $rows[$monthLabel]->total : 0,
            ];
        }

        return $filled;
    }

    private function gamesLast7Days(): array
    {
        $from = Carbon::now()->subDays(6)->startOfDay();

        $rows = Game::query()
            ->selectRaw("TO_CHAR(COALESCE(ended_at, began_at), 'YYYY-MM-DD') as day, COUNT(*) as total")
            ->where(function ($query) use ($from) {
                $query->whereNotNull('ended_at')
                    ->where('ended_at', '>=', $from)
                    ->orWhere(function ($q) use ($from) {
                        $q->whereNull('ended_at')
                            ->whereNotNull('began_at')
                            ->where('began_at', '>=', $from);
                    });
            })
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        // Preenche os últimos 7 dias (inclui dias sem jogos com total 0)
        $filled = [];
        for ($i = 0; $i < 7; $i++) {
            $dayLabel = $from->copy()->addDays($i)->format('Y-m-d');
            $filled[] = [
                'day' => $dayLabel,
                'total' => isset($rows[$dayLabel]) ? (int) $rows[$dayLabel]->total : 0,
            ];
        }

        return $filled;
    }
}