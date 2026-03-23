<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
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

        if ($user->type !== 'A') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas administradores podem usar esta rota.',
            ], 403);
        }

        return $next($request);
    }
}
