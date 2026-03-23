<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use \App\Notifications\Auth\VerifyEmail\QueueVerifyEmail;
use \App\Notifications\Auth\ResetPassword\QueueResetPassword;
use App\Notifications\Auth\ResetPassword\QueuePasswordChangedNotification;

/**
 * @method \Laravel\Sanctum\PersonalAccessToken|null currentAccessToken()
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'nickname',
        'blocked',
        'photo_avatar_filename',
        'coins_balance',
        'custom',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'deleted_at' => 'datetime',
        'password' => 'hashed',
        'blocked' => 'boolean',
        'coins_balance' => 'integer',
        'custom' => 'array',
    ];

    public function sendEmailVerificationNotification()
    {
        $this->notify(new QueueVerifyEmail());
    }

    public function gamesAsPlayer1()
    {
        return $this->hasMany(Game::class, 'player1_user_id');
    }

    public function gamesAsPlayer2()
    {
        return $this->hasMany(Game::class, 'player2_user_id');
    }

    public function matchesAsPlayer1()
    {
        return $this->hasMany(MatchModel::class, 'player1_user_id');
    }

    public function matchesAsPlayer2()
    {
        return $this->hasMany(MatchModel::class, 'player2_user_id');
    }

    public function gamesWon()
    {
        return $this->hasMany(Game::class, 'winner_user_id');
    }

    public function matchesWon()
    {
        return $this->hasMany(MatchModel::class, 'winner_user_id');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new QueueResetPassword($token));
    }

    public function sendPasswordChangedNotification()
    {
        $this->notify(new QueuePasswordChangedNotification());
    }

    public function transactions()
    {
        return $this->hasMany(CoinTransaction::class);
    }

    public function purchases()
    {
        return $this->hasMany(CoinPurchase::class);
    }

    public function isAdmin(): bool
    {
        return $this->type === 'A';
    }

    public function isPlayer(): bool
    {
        return $this->type === 'P';
    }
}
