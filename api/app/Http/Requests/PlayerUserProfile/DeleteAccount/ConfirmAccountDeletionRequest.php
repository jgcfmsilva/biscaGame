<?php

namespace App\Http\Requests\PlayerUserProfile\DeleteAccount;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfirmAccountDeletionRequest extends FormRequest
{
    protected ?User $targetUser = null;

    public function authorize(): bool
    {
        if (! $this->hasValidSignature()) {
            $this->failWithJson('Pedido de eliminação expirou ou é inválido.', 403);
        }

        $this->targetUser = User::find($this->route('id'));

        if (! $this->targetUser) {
            $this->failWithJson('Utilizador não encontrado.', 404);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'A password é obrigatória.',
        ];
    }

    public function targetUser(): ?User
    {
        return $this->targetUser;
    }

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
