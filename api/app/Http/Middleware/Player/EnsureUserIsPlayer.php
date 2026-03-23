<?php

namespace App\Http\Middleware\Player;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsPlayer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Autenticação necessária.',
            ], 401);
        }

        if ($user->type === 'A') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas jogadores podem usar esta rota.',
            ], 403);
        }

        return $next($request);
    }
}
