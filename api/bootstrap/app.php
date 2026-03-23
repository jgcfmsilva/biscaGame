<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: [
            __DIR__.'/../routes/api.php'
        ],
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Para API/SPA: não redirecionar convidados para rota login; retornar 401/403.
        $middleware->redirectGuestsTo(fn () => null);
        $middleware->alias([
            'verified' => \App\Http\Middleware\Email\EnsureEmailIsVerifiedJson::class,
            'player' => \App\Http\Middleware\Player\EnsureUserIsPlayer::class,
            'admin' => \App\Http\Middleware\Admin\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Resposta JSON customizada para falta de autenticação em APIs protegidas.
        $exceptions->render(function (AuthenticationException $e, $request): JsonResponse {
            return response()->json([
                'success' => false,
                'message' => 'Autenticação necessária',
            ], 401);
        });
    })->create();
