<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Game extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected static bool $sequenceSynced = false;

    protected $fillable = [
        'id',
        'type',
        'player1_user_id',
        'player2_user_id',
        'is_draw',
        'winner_user_id',
        'loser_user_id',
        'match_id',
        'status',
        'began_at',
        'ended_at',
        'total_time',
        'player1_points',
        'player2_points',
        'custom',
    ];

    protected $casts = [
        'is_draw' => 'boolean',
        'began_at' => 'datetime',
        'ended_at' => 'datetime',
        'custom' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (Game $game) {
            if ($game->id) {
                return;
            }

            static::syncSequence();
        });
    }

    private static function syncSequence(): void
    {
        if (static::$sequenceSynced) {
            return;
        }

        DB::statement("
            SELECT setval(
                'games_id_seq',
                COALESCE((SELECT MAX(id) FROM games), 0)
            )
        ");

        static::$sequenceSynced = true;
    }

    public function player1()
    {
        return $this->belongsTo(User::class, 'player1_user_id');
    }

    public function player2()
    {
        return $this->belongsTo(User::class, 'player2_user_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function loser()
    {
        return $this->belongsTo(User::class, 'loser_user_id');
    }

    public function match()
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }

    public function coinTransactions()
    {
        return $this->hasMany(CoinTransaction::class);
    }
}
