<?php

namespace App\Http\Controllers\PlayerUserProfile\MatchHistory;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\MatchModel;
use Illuminate\Http\Request;

class PlayerMatchHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $limit = (int) $request->query('limit', 5);
        $limit = max(1, min($limit, 25));

        $games = Game::with([
                'player1:id,nickname',
                'player2:id,nickname',
                'winner:id,nickname',
                'loser:id,nickname',
            ])
            ->whereNull('match_id')
            ->where(function ($query) use ($user) {
                $query->where('player1_user_id', $user->id)
                    ->orWhere('player2_user_id', $user->id);
            })
            ->where('status', 'Ended')
            ->orderByDesc('began_at')
            ->limit($limit)
            ->get();

        $matches = MatchModel::with([
                'player1:id,nickname',
                'player2:id,nickname',
                'winner:id,nickname',
                'loser:id,nickname',
                'games' => function ($query) {
                    $query->orderBy('began_at', 'asc')
                        ->orderBy('id', 'asc');
                },
            ])
            ->where(function ($query) use ($user) {
                $query->where('player1_user_id', $user->id)
                    ->orWhere('player2_user_id', $user->id);
            })
            ->where('status', 'Ended')
            ->orderByDesc('began_at')
            ->limit($limit)
            ->get();

        $gamePayload = $games->map(fn (Game $game) => $this->formatStandaloneGame($game, $user->id));
        $payload = $matches->map(fn (MatchModel $match) => $this->formatMatch($match, $user->id));

        return response()->json([
            'games' => $gamePayload,
            'matches' => $payload,
        ]);
    }

    public function paginated(Request $request)
    {
        $user = $request->user();

        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $status = $request->query('status');
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'id');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSort = ['id', 'created_at', 'ended_at', 'stake'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'id';
        }

        $query = MatchModel::with([
                'player1:id,nickname,photo_avatar_filename',
                'player2:id,nickname,photo_avatar_filename',
                'winner:id,nickname',
                'loser:id,nickname',
            ])
            ->where(function ($q) use ($user) {
                $q->where('player1_user_id', $user->id)
                    ->orWhere('player2_user_id', $user->id);
            });

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('player1', function ($q2) use ($search) {
                        $q2->where('nickname', 'like', "%{$search}%");
                    })
                    ->orWhereHas('player2', function ($q2) use ($search) {
                        $q2->where('nickname', 'like', "%{$search}%");
                    });
            });
        }

        $query->orderBy($sortBy, $sortDir);

        return response()->json($query->paginate($perPage));
    }

    protected function formatMatch(MatchModel $match, int $userId): array
    {
        $playerMap = [
            $match->player1_user_id => [
                'id'       => $match->player1?->id,
                'nickname' => $match->player1?->nickname,
            ],
            $match->player2_user_id => [
                'id'       => $match->player2?->id,
                'nickname' => $match->player2?->nickname,
            ],
        ];

        return [
            'id'               => $match->id,
            'type'             => $match->type,
            'stake'           => $match->stake,
            'status'          => $match->status,
            'forfeit_reason'  => $match->custom['forfeit_reason'] ?? null,
            'player1_marks'   => $match->player1_marks,
            'player2_marks'   => $match->player2_marks,
            'player1_points'  => $match->player1_points,
            'player2_points'  => $match->player2_points,
            'began_at'        => optional($match->began_at)->toIso8601String(),
            'ended_at'        => optional($match->ended_at)->toIso8601String(),
            'total_time'      => $match->total_time,
            'player1'         => $playerMap[$match->player1_user_id] ?? null,
            'player2'         => $playerMap[$match->player2_user_id] ?? null,
            'winner'          => $match->winner?->only(['id', 'nickname']),
            'loser'           => $match->loser?->only(['id', 'nickname']),
            'perspective'     => [
                'viewer_is_player1' => $match->player1_user_id === $userId,
                'viewer_is_player2' => $match->player2_user_id === $userId,
            ],
            'games' => $match->games->map(
                fn (Game $game) => $this->formatGame($game, $playerMap)
            )->values(),
        ];
    }

    protected function formatGame(Game $game, array $playerMap): array
    {
        $roundsRaw = $game->custom['rounds'] ?? [];
        $legacyTricks = $game->custom['tricks'] ?? [];

        // Se não houver rounds (legacy), tenta reconstruir a partir de tricks antigos.
        if (empty($roundsRaw) && !empty($legacyTricks)) {
            $roundsRaw = collect($legacyTricks)->map(function (array $trick, int $index) use ($game) {
                $winnerKey = $trick['winner'] ?? null;
                $winnerId = $winnerKey === 'player1'
                    ? $game->player1_user_id
                    : ($winnerKey === 'player2' ? $game->player2_user_id : null);

                return [
                    'round_number'   => $trick['trick'] ?? ($index + 1),
                    'lead_player_id' => null,
                    'winner_user_id' => $winnerId,
                    'player1_card'   => $trick['player1_card'] ?? null,
                    'player2_card'   => $trick['player2_card'] ?? null,
                    'player1_card_points' => $trick['player1_card_points'] ?? null,
                    'player2_card_points' => $trick['player2_card_points'] ?? null,
                    'player1_total_points' => $trick['player1_total_points'] ?? null,
                    'player2_total_points' => $trick['player2_total_points'] ?? null,
                    'points_awarded' => ($trick['player1_card_points'] ?? 0) + ($trick['player2_card_points'] ?? 0),
                    'trump_suit'     => $trick['trump_suit'] ?? null,
                    'final_phase'    => false,
                    'timestamp'      => $trick['timestamp'] ?? null,
                ];
            })->all();
        }

        $rounds = collect($roundsRaw)
            ->map(function (array $round, int $index) use ($playerMap) {
                $leadId = $round['lead_player_id'] ?? null;
                $winnerId = $round['winner_user_id'] ?? null;

                return [
                    'round_number'   => $round['round_number'] ?? ($index + 1),
                    'lead_player'    => $leadId ? ($playerMap[$leadId] ?? ['id' => $leadId]) : null,
                    'winner'         => $winnerId ? ($playerMap[$winnerId] ?? ['id' => $winnerId]) : null,
                    'player1_card'   => $round['player1_card'] ?? null,
                    'player2_card'   => $round['player2_card'] ?? null,
                    'points_awarded' => $round['points_awarded'] ?? null,
                    'trump_suit'     => $round['trump_suit'] ?? null,
                    'final_phase'    => (bool) ($round['final_phase'] ?? false),
                    'timestamp'      => $round['timestamp'] ?? null,
                ];
            })
            ->values();

        $trickSource = !empty($roundsRaw) ? $roundsRaw : $legacyTricks;
        $tricks = collect($trickSource)
            ->map(function (array $trick, int $index) use ($playerMap, $game) {
                $winnerId = $trick['winner_user_id'] ?? null;
                if (!$winnerId && isset($trick['winner'])) {
                    $winnerId = $trick['winner'] === 'player1'
                        ? $game->player1_user_id
                        : ($trick['winner'] === 'player2' ? $game->player2_user_id : null);
                }

                return [
                    'trick' => $trick['trick'] ?? $trick['round_number'] ?? ($index + 1),
                    'winner' => $winnerId ? ($playerMap[$winnerId] ?? ['id' => $winnerId]) : null,
                    'player1_card' => $trick['player1_card'] ?? null,
                    'player2_card' => $trick['player2_card'] ?? null,
                    'player1_card_points' => $trick['player1_card_points'] ?? null,
                    'player2_card_points' => $trick['player2_card_points'] ?? null,
                    'player1_total_points' => $trick['player1_total_points'] ?? null,
                    'player2_total_points' => $trick['player2_total_points'] ?? null,
                    'timestamp' => $trick['timestamp'] ?? null,
                ];
            })
            ->values();

        return [
            'id'              => $game->id,
            'status'          => $game->status,
            'began_at'        => optional($game->began_at)->toIso8601String(),
            'ended_at'        => optional($game->ended_at)->toIso8601String(),
            'total_time'      => $game->total_time,
            'player1_points'  => $game->player1_points,
            'player2_points'  => $game->player2_points,
            'player1'         => $playerMap[$game->player1_user_id] ?? null,
            'player2'         => $playerMap[$game->player2_user_id] ?? null,
            'is_draw'         => (bool) $game->is_draw,
            'winner_user_id'  => $game->winner_user_id,
            'loser_user_id'   => $game->loser_user_id,
            'marks_awarded'   => $game->custom['marks_awarded'] ?? null,
            'rounds'          => $rounds,
            'tricks'          => $tricks,
            'trump_card'      => $game->custom['trump_card'] ?? null,
            'forfeit_reason'  => $game->custom['forfeit_reason'] ?? null,
        ];
    }

    protected function formatStandaloneGame(Game $game, int $userId): array
    {
        $playerMap = [
            $game->player1_user_id => [
                'id'       => $game->player1?->id,
                'nickname' => $game->player1?->nickname,
            ],
            $game->player2_user_id => [
                'id'       => $game->player2?->id,
                'nickname' => $game->player2?->nickname,
            ],
        ];

        return [
            ...$this->formatGame($game, $playerMap),
            'type'        => $game->type,
            'stake'       => $game->stake ?? null,
            'trump_card'  => $game->custom['trump_card'] ?? null,
            'winner'      => $game->winner?->only(['id', 'nickname']),
            'loser'       => $game->loser?->only(['id', 'nickname']),
            'player1'     => $playerMap[$game->player1_user_id] ?? null,
            'player2'     => $playerMap[$game->player2_user_id] ?? null,
            'perspective' => [
                'viewer_is_player1' => $game->player1_user_id === $userId,
                'viewer_is_player2' => $game->player2_user_id === $userId,
            ],
        ];
    }
}
