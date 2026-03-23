<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoinTransactionType extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = ['name', 'type', 'custom'];

    protected $casts = [
        'custom' => 'array',
    ];

    public function transactions()
    {
        return $this->hasMany(CoinTransaction::class, 'coin_transaction_type_id');
    }
}
