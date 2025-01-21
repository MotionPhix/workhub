<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UserRegistrationRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // Adjust based on your authorization logic
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
        Password::defaults()
          ->min(12)
          ->letters()
          ->mixedCase()
          ->numbers()
          ->symbols()
          ->uncompromised()
      ],
      'department' => ['nullable', 'string', 'max:100'],
      'manager_email' => ['nullable', 'email', 'exists:users,email'],
    ];
  }

  public function messages(): array
  {
    return [
      'email.unique' => 'An account with this email already exists.',
      'password.uncompromised' => 'The password has been compromised in a data breach. Please choose a different password.',
    ];
  }
}
