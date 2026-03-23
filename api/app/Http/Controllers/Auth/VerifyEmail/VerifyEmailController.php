<?php

namespace App\Http\Controllers\Auth\VerifyEmail;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmail\VerifyEmailRequest;
use Illuminate\Auth\Events\Verified;

class VerifyEmailController extends Controller
{
    public function __invoke(VerifyEmailRequest $request, int $id, string $hash)
    {
        $user = $request->targetUser();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email já se encontrava verificado.',
            ]);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json([
            'success' => true,
            'message' => 'Email verificado com sucesso.',
        ]);
    }
}
