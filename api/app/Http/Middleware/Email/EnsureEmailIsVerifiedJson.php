<?php

namespace App\Http\Middleware\Email;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureEmailIsVerifiedJson extends EnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        /** @var \Illuminate\Contracts\Auth\MustVerifyEmail|\Illuminate\Foundation\Auth\User|null $user */
        $user = $request->user();

        if (! $user || ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Email não verificado.',
            ], 403);
        }

        return $next($request);
    }
}
