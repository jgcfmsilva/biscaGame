<?php

namespace App\Http\Requests\Auth\Login;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'O endereço de email é obrigatório.',
            'email.email'       => 'O endereço de email não é válido.',
            'password.required' => 'A palavra-passe é obrigatória.',
        ];
    }

    /**
     * Quando a validação dos CAMPOS falha → 422 em JSON.
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $firstMessage = $errors->first() ?: 'Dados inválidos.';

        throw new HttpResponseException(
            response()->json([
                'message' => $firstMessage,
                'errors'  => $errors,
            ], 422)
        );
    }

    /**
     * Valida as credenciais e devolve o utilizador autenticado.
     * NÃO usa sessão. Apenas verifica email + password.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        // Garante que as regras de validação já foram aplicadas
        $this->validated();

        /** @var User|null $user */
        $user = User::where('email', $this->input('email'))->first();

        if (! $user || ! Hash::check($this->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        return $user;
    }
}
