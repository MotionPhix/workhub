<?php

namespace App\Http\Requests\Auth;

use App\Rules\StrongPasswordRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name' => ['required', 'string', 'max:255'],
      'email' => [
        'required',
        'string',
        'email',
        'max:255',
        'unique:users,email'
      ],
      'password' => [
        'required',
        'confirmed',
        new StrongPasswordRule(),
      ],
      'department' => ['nullable', 'string', 'max:100'],
      // 'terms' => ['accepted']
    ];
  }

  public function messages(): array
  {
    return [
      'email.unique' => 'An account with this email already exists.',
      // 'terms.accepted' => 'You must accept the terms and conditions.'
    ];
  }
}
