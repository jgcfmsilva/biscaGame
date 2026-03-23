<?php

namespace App\Http\Controllers\Auth\ForgetPassword;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgetPassword\PasswordResetTokenVerifyRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class PasswordResetTokenVerifyController extends Controller
{
    public function __invoke(PasswordResetTokenVerifyRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'Nenhum utilizador encontrado com esse email.',
            ], 404);
        }

        $repository = Password::getRepository();

        $isValid = $repository->exists($user, $data['token']);

        if (! $isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido ou expirado.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token válido.',
        ], 200);
    }
}
