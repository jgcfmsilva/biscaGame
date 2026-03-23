<?php

namespace App\Http\Requests\Auth\VerifyEmail;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\URL;

class VerifyEmailRequest extends FormRequest
{
    protected ?User $targetUser = null;

    public function authorize(): bool
    {
        if (! URL::hasValidSignature($this)) {
            $this->failWithJson('Verification link is invalid or expired.', 401);
        }

        $this->targetUser = User::find($this->route('id'));

        if (! $this->targetUser) {
            $this->failWithJson('User not found.', 404);
        }

        $hash = $this->route('hash');

        if (! hash_equals(sha1($this->targetUser->getEmailForVerification()), (string) $hash)) {
            $this->failWithJson('Verification hash is invalid.', 403);
        }

        return true;
    }

    public function rules(): array
    {
        return [];
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
