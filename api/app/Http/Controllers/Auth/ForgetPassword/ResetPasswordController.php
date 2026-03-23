<?php

namespace App\Http\Controllers\Auth\ForgetPassword;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgetPassword\ResetPasswordRequest;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $status = Password::reset(
            $data,
            fn ($user, $password) => PasswordResetService::resetUserPassword($user, $password)
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password atualizada com sucesso.',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => __($status),
        ], 422);
    }
}
