<?php

namespace App\Http\Requests\PlayerUserProfile\PlayerInfo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Models\User;

class UpdatePlayerInfoRequest extends FormRequest
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
            $this->failWithJson('Não tens permissão para atualizar este perfil.', 403);
        }

        if ($this->targetUser->email_verified_at === null) {
            $this->failWithJson('Tens de verificar o email antes de atualizar os dados.', 403);
        }

        return true;
    }

    public function rules(): array
    {
        $userId = $this->targetUser?->id ?? $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'nickname' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'nickname')->ignore($userId),
            ],
            'photo_avatar_filename' => ['sometimes', 'nullable', 'string', 'max:255'],
            'photo' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Este email já está a ser usado.',
            'nickname.unique' => 'Este nickname já está a ser usado.',
            'photo.image' => 'O ficheiro deve ser uma imagem válida.',
            'photo.max' => 'A imagem não pode exceder 5MB.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $firstMessage = $errors->first() ?: 'Dados inválidos.';

        throw new HttpResponseException(
            response()->json([
                'message' => $firstMessage,
                'errors' => $errors,
            ], 422)
        );
    }

    public function targetUser(): ?User
    {
        return $this->targetUser;
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
