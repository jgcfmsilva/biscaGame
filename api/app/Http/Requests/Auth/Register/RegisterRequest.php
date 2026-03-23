<?php

namespace App\Http\Requests\Auth\Register;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'nickname' => ['required','string','max:50','unique:users,nickname'],
            // >8 caracteres => min 9; exige maiúscula, dígito e caracter especial
            'password' => [
                'required',
                'string',
                'min:9',
                'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
            'photo' => ['sometimes','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'     => 'Email já existe.',
            'nickname.unique'  => 'Nickname já existe.',
            'password.min'     => 'Password tem de ter mais de 8 caracteres.',
            'password.regex'   => 'Password precisa de maiúscula, dígito e caracter especial.',
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
