<?php

namespace App\Http\Requests\Auth\Login;

use Illuminate\Foundation\Http\FormRequest;

class LogoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Rota já protegida por auth:sanctum.
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
