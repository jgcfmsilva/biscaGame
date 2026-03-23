<?php

namespace App\Http\Requests\PlayerUserProfile\DeleteAccount;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RequestAccountDeletionRequest extends FormRequest
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
            $this->failWithJson('Não tens permissão para eliminar esta conta.', 403);
        }

        return true;
    }

    public function rules(): array
    {
        return [
        ];
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
