<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'transaction_datetime',
        'user_id',
        'coin_transaction_type_id',
        'coins',
        'game_id',
        'match_id',
        'custom'
    ];

    protected $casts = [
        'transaction_datetime' => 'datetime',
        'custom' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function match()
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function type()
    {
        return $this->belongsTo(CoinTransactionType::class, 'coin_transaction_type_id');
    }

    public function purchase()
    {
        return $this->hasOne(CoinPurchase::class);
    }

    protected static function booted()
    {
        static::creating(function (CoinTransaction $tx) {
            if (!$tx->id) {
                $maxId = static::max('id') ?? 0;
                $tx->id = $maxId + 1;
            }
        });
    }
}
