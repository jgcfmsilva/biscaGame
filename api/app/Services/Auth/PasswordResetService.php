<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetService
{
    public static function resetUserPassword(User $user, string $password): void
    {
        $mustChange = $user->type === 'A' && ($user->custom['must_change_password'] ?? false);
        $custom = $user->custom ?? [];

        if ($mustChange) {
            unset($custom['must_change_password']);
        }

        $user->forceFill([
            'password'          => Hash::make($password),
            'remember_token'    => null,
            'email_verified_at' => $mustChange ? now() : $user->email_verified_at,
            'custom'            => $custom,
        ])->save();

        // Revoga todos os tokens ativos para forçar novo login.
        $user->tokens()->delete();

        // Notifica o utilizador de que a password foi alterada.
        $user->sendPasswordChangedNotification();
    }
}
