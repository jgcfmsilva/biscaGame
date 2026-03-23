<?php

namespace App\Http\Requests\Auth\ForgetPassword;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PasswordResetTokenVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email é obrigatório.',
            'email.email'    => 'Formato de email inválido.',
            'email.exists'   => 'Nenhum utilizador encontrado com esse email.',
            'token.required' => 'Token é obrigatório.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $firstMessage = $errors->first() ?: 'Dados inválidos.';

        throw new HttpResponseException(response()->json([
            'message' => $firstMessage,
            'errors'  => $errors,
        ], 422));
    }
}
