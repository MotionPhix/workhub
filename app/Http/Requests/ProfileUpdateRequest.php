<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'name' => ['required', 'string', 'max:255'],
      'department_uuid' => ['nullable', 'exists:departments,uuid'],
      'gender' => ['nullable', Rule::in(['male', 'female'])],
      'job_title' => ['required', 'string'],
      'manager_email' => ['nullable', 'email', 'exists:users,email'],
      'settings' => ['nullable', 'array'],
      'settings.timezone' => [
        'nullable', Rule::in(\DateTimeZone::listIdentifiers())
      ],
      'email' => [
        'required',
        'string',
        'lowercase',
        'email',
        'max:255',
        Rule::unique(User::class)->ignore($this->user()->id),
      ],
    ];
  }

  /**
   * Custom error messages for validation.
   *
   * @return array<string, string>
   */
  public function messages(): array
  {
    return [
      'name.required' => 'The name field is required.',
      'name.max' => 'The name cannot exceed 255 characters.',
      'department_uuid.exists' => 'The selected department is invalid.',
      'manager_email.email' => 'The manager email must be a valid email address.',
      'manager_email.exists' => 'The manager email does not exist in the system.',
      'settings.array' => 'The settings field must be an array.',
      'settings.timezone.in' => 'The selected timezone is invalid.',
      'email.required' => 'The email field is required.',
      'email.email' => 'Provide a valid email address.',
      'email.unique' => 'The email address is already in use.',
    ];
  }
}
