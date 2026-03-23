<?php

namespace App\Services\Auth;

use App\Models\User;

class LoginGuardService
{
    /**
     * Returns a deny reason (status/message) when login should be blocked, or null if allowed.
     */
    public function denyReason(User $user): ?array
    {
        if ($user->type === 'A' && ($user->custom['must_change_password'] ?? false)) {
            return [
                'status'  => 403,
                'message' => 'É necessário alterar a password antes de iniciar sessão.',
            ];
        }

        return null;
    }
}
