<?php

namespace App\Http\Requests\PlayerUserProfile\UpdatePassword;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePasswordRequest extends FormRequest
{
    protected ?User $targetUser = null;

    public function authorize(): bool
    {
        $this->targetUser = User::find($this->route('id'));

        if (! $this->targetUser) {
            $this->failWithJson('Utilizador não encontrado.', 404);
        }

        /** @var User|null $authUser */
        $authUser = $this->user();

        if ($authUser?->id !== $this->targetUser->id) {
            $this->failWithJson('Não tens permissão para alterar esta password.', 403);
        }

        if ($this->targetUser->email_verified_at === null) {
            $this->failWithJson('Tens de verificar o email antes de alterar a password.', 403);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'current_password'      => ['required', 'current_password:sanctum'],
            'password'              => [
                'required',
                'string',
                'min:9',
                'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[\\W_]).+$/',
                'confirmed',
            ],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'A password atual é obrigatória.',
            'current_password.current_password' => 'A password atual não corresponde.',
            'password.required'         => 'A nova password é obrigatória.',
            'password.min'              => 'Password tem de ter mais de 8 caracteres.',
            'password.regex'            => 'Password precisa de maiúscula, dígito e caracter especial.',
            'password.confirmed'        => 'A confirmação da password não corresponde.',
            'password_confirmation.required' => 'É necessária a confirmação da password.',
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

    protected function failWithJson(string $message, int $status): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $message,
            ], $status)
        );
    }
}
