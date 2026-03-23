<?php

namespace App\Http\Requests\PlayerUserProfile\DeleteAccount;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidateAccountDeletionLinkRequest extends FormRequest
{
    protected ?User $targetUser = null;

    public function authorize(): bool
    {
        if (! $this->hasValidSignature()) {
            $this->failWithJson('Deletion request expired or is invalid.', 403);
        }

        $this->targetUser = User::find($this->route('id'));

        if (! $this->targetUser) {
            $this->failWithJson('User not found.', 404);
        }

        return true;
    }

    public function rules(): array
    {
        return [];
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
