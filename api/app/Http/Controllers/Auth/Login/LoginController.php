<?php

namespace App\Http\Controllers\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

use App\Http\Requests\Auth\Login\LoginRequest;
use App\Http\Requests\Auth\Login\LogoutRequest;

use App\Services\ActiveAdmins\ActiveAdminCacheService;
use App\Services\Auth\LoginGuardService;
use App\Services\ActivePlayers\ActivePlayerCacheService;
use Illuminate\Support\Str;



class LoginController extends Controller
{
    public function __construct(
        private readonly ActiveAdminCacheService $activeAdminCache,
        private readonly LoginGuardService $loginGuard,
        private readonly ActivePlayerCacheService $activePlayerCache,
    )
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = $request->authenticate();
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed'),
            ], 401);
        }

        if ($deny = $this->loginGuard->denyReason($user)) {
            return response()->json([
                'success' => false,
                'message' => $deny['message'],
            ], $deny['status']);
        }

        // LOGIN BEM SUCEDIDO → CRIAR TOKEN SANCTUM
        $token = $user->createToken('api_token')->plainTextToken;

        // "Remember me": persist token de recuperação simples associado ao utilizador
        $remember = $request->boolean('remember', false);
        if ($remember) {
            $user->forceFill(['remember_token' => Str::random(60)])->save();
        } elseif ($user->remember_token) {
            // limpa token anterior se existia
            $user->forceFill(['remember_token' => null])->save();
        }

        // Se for admin, guardar info mínima em cache para marcar sessão ativa
        $this->activeAdminCache->markLogin($user);
        // Se for player (não admin), guardar presença ativa separada
        $this->activePlayerCache->markLogin($user);

        return response()->json([
            'success'    => true,
            'message'    => 'Sessão iniciada com sucesso.',
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => $user,
        ], 200);
    }

    public function logout(LogoutRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilizador não autenticado.',
            ], 401);
        }

        // Apaga apenas o token atual (Bearer token usado nesta requisição)
        $user->currentAccessToken()?->delete();

        // Limpa presença em cache se for admin e regista hora de logout
        $this->activeAdminCache->markLogout($user);
        // Limpa presença em cache se for player
        $this->activePlayerCache->markLogout($user);

        return response()->json([
            'success' => true,
            'message' => 'Sessão terminada com sucesso.',
        ], 200);
    }
}
