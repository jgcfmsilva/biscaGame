<?php

namespace App\Http\Requests\Admin\DeletingAdmins;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeleteAdminRequest extends FormRequest
{
    protected ?User $targetAdmin = null;

    public function authorize(): bool
    {
        $this->targetAdmin = User::find($this->route('id'));

        if (! $this->targetAdmin) {
            $this->failWithJson('Administrador não encontrado.', 404);
        }

        if ($this->targetAdmin->type !== 'A') {
            $this->failWithJson('Só administradores podem ser eliminados.', 403);
        }

        if (! ($this->targetAdmin->custom['must_change_password'] ?? false)) {
            $this->failWithJson('O administrador só pode ser eliminado enquanto tiver de alterar a password.', 403);
        }

        if ($this->targetAdmin->email_verified_at !== null) {
            $this->failWithJson('O administrador só pode ser eliminado antes de verificar o email.', 403);
        }

        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function targetAdmin(): ?User
    {
        return $this->targetAdmin;
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
