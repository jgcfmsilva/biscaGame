<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\MatchModel;
use App\Models\User;
use App\Support\MatchPresenter;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class LobbyController extends Controller
{
    public function openGames()
    {
        return response()->json([
            'games' => $this->fetchOpenGames(),
        ]);
    }

    public function openMatches()
    {
        return response()->json([
            'matches' => $this->fetchOpenMatches(),
        ]);
    }

    public function active()
    {
        $games = $this->fetchOpenGames();
        $matches = $this->fetchOpenMatches();

        $openGamesCount = $games->count();
        $openMatchesCount = $matches->count();

        return response()->json([
            'games'   => $games,
            'matches' => $matches,
            'stats'   => [
                'games_playing'  => $this->countPlayingStandaloneGames(),
                'matches_playing'=> $this->countPlayingMatches(),
                'games_open'     => $openGamesCount,
                'matches_open'   => $openMatchesCount,
                'total'          => $openGamesCount + $openMatchesCount,
                'active_users'   => $this->countActiveUsers(),
            ],
        ]);
    }

    private function fetchOpenGames()
    {
        $games = Game::with('player1:id,nickname')
            ->whereNull('match_id')
            ->where('status', 'Pending')
            ->orderByDesc('id')
            ->get();

        // filtra apenas os que estão marcados como a aguardar adversário
        return $games->filter(function ($game) {
            return ($game->custom['waiting_for_opponent'] ?? false) === true;
        })->values();
    }

    private function fetchOpenMatches()
    {
        return MatchModel::with('player1:id,nickname', 'player2:id,nickname')
            ->where('status', 'Pending')
            ->where('custom->waiting_for_opponent', true)
            ->orderByDesc('id')
            ->get()
            ->map(fn (MatchModel $match) => MatchPresenter::format($match));
    }

    private function countActiveUsers(): int
    {
        $threshold = Carbon::now()->subMinutes(5);

        try {
            return PersonalAccessToken::query()
                ->where('tokenable_type', User::class)
                ->where(function ($query) use ($threshold) {
                    $query->where('last_used_at', '>=', $threshold)
                        ->orWhere('updated_at', '>=', $threshold)
                        ->orWhere('created_at', '>=', $threshold);
                })
                ->distinct('tokenable_id')
                ->count('tokenable_id');
        } catch (\Throwable $e) {
            report($e);
            return 0;
        }
    }

    private function countPlayingStandaloneGames(): int
    {
        return Game::query()
            ->whereNull('match_id')
            ->where('status', 'Playing')
            ->where('began_at', '>=', Carbon::now()->subMinutes(5))
            ->count();
    }

    private function countPlayingMatches(): int
    {
        return MatchModel::query()
            ->where('status', 'Playing')
            ->where('began_at', '>=', Carbon::now()->subMinutes(5))
            ->count();
    }
}
