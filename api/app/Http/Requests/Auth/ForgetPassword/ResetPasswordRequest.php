<?php

namespace App\Http\Requests\Auth\ForgetPassword;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'                 => ['required', 'string'],
            'email'                 => ['required', 'email', 'exists:users,email'],
            'password'              => [
                'required',
                'string',
                'min:9',
                'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                'confirmed',
            ],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'     => 'Token é obrigatório.',
            'email.required'     => 'Email é obrigatório.',
            'email.email'        => 'Formato de email inválido.',
            'email.exists'       => 'Nenhum utilizador encontrado com esse email.',
            'password.required'  => 'Password é obrigatória.',
            'password.min'       => 'Password tem de ter pelo menos 9 caracteres.',
            'password.regex'     => 'Password precisa de maiúscula, dígito e caracter especial.',
            'password.confirmed' => 'A confirmação da password não corresponde.',
            'password_confirmation.required' => 'A confirmação da password é obrigatória.',
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
