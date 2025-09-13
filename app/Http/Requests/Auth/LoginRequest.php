<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::exists('users', 'email')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
            'password' => [
                'required',
                'string',
            ],
            'remember' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'No active account found with this email address.',
        ];
    }
}
