<?php

namespace App\Http\Requests\Auth\ForgetPassword;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PasswordResetVerifyEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O email e obrigatorio.',
            'email.email'    => 'Formato de email invalido.',
            'email.exists'   => 'Não existe utilizador com esse email.',
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
