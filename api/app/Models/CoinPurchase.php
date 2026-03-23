<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinPurchase extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'purchase_datetime',
        'user_id',
        'coin_transaction_id',
        'euros',
        'payment_type',
        'payment_reference',
        'custom'
    ];

    protected $casts = [
        'purchase_datetime' => 'datetime',
        'euros' => 'decimal:2',
        'custom' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(CoinTransaction::class, 'coin_transaction_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
