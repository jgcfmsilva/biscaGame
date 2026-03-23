<?php

namespace App\Http\Controllers\Auth\ForgetPassword;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;

use App\Http\Requests\Auth\ForgetPassword\PasswordResetVerifyEmailRequest;


class RequestPasswordResetController extends Controller
{
    public function send(PasswordResetVerifyEmailRequest $request)
    {
        $status = Password::sendResetLink(
            $request->validated()
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Enviámos o link de recuperação.'], 200);
        }

        return response()->json(['message' => 'Não foi possível enviar o link de recuperação.'], 422);
    }
}
