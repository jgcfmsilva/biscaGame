<?php

namespace App\Http\Controllers\PlayerUserProfile\UpdatePassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Http\Requests\PlayerUserProfile\UpdatePassword\UpdatePasswordRequest;

use App\Notifications\Auth\ResetPassword\QueuePasswordChangedNotification;


class UpdatePasswordController extends Controller
{
    public function updatePassword(UpdatePasswordRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validated();

        if (Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'A nova password deve ser diferente da atual.',
            ], 422);
        }

        $custom = $user->custom ?? [];
        if (isset($custom['must_change_password'])) {
            $custom['must_change_password'] = false;
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => null,
            'custom' => $custom,
        ])->save();

        $user->tokens()->delete();

        $user->notify(new QueuePasswordChangedNotification());

        return response()->json([
            'success' => true,
            'message' => 'Password atualizada. A sessão será terminada em 3 segundos.',
        ]);
    }
}
