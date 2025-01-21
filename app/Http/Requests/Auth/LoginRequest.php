<?php
//
//namespace App\Http\Requests\Auth;
//
//use Illuminate\Auth\Events\Lockout;
//use Illuminate\Foundation\Http\FormRequest;
//use Illuminate\Support\Facades\Auth;
//use Illuminate\Support\Facades\RateLimiter;
//use Illuminate\Support\Str;
//use Illuminate\Validation\ValidationException;
//
//class LoginRequest extends FormRequest
//{
//  /**
//   * Determine if the user is authorized to make this request.
//   */
//  public function authorize(): bool
//  {
//    return true;
//  }
//
//  /**
//   * Get the validation rules that apply to the request.
//   *
//   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
//   */
//  public function rules(): array
//  {
//    return [
//      'email' => ['required', 'string', 'email'],
//      'password' => ['required', 'string'],
//    ];
//  }
//
//  /**
//   * Attempt to authenticate the request's credentials.
//   *
//   * @throws \Illuminate\Validation\ValidationException
//   */
//  public function authenticate(): void
//  {
//    $this->ensureIsNotRateLimited();
//
//    if (!Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
//      RateLimiter::hit($this->throttleKey());
//
//      throw ValidationException::withMessages([
//        'email' => trans('auth.failed'),
//      ]);
//    }
//
//    RateLimiter::clear($this->throttleKey());
//  }
//
//  /**
//   * Ensure the login request is not rate limited.
//   *
//   * @throws \Illuminate\Validation\ValidationException
//   */
//  public function ensureIsNotRateLimited(): void
//  {
//    if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
//      return;
//    }
//
//    event(new Lockout($this));
//
//    $seconds = RateLimiter::availableIn($this->throttleKey());
//
//    throw ValidationException::withMessages([
//      'email' => trans('auth.throttle', [
//        'seconds' => $seconds,
//        'minutes' => ceil($seconds / 60),
//      ]),
//    ]);
//  }
//
//  /**
//   * Get the rate limiting throttle key for the request.
//   */
//  public function throttleKey(): string
//  {
//    return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
//  }
//}


namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\StrongPasswordRule;

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
        })
      ],
      'password' => [
        'required',
        'string',
        new StrongPasswordRule()
      ],
      'remember' => 'sometimes|boolean'
    ];
  }

  public function messages(): array
  {
    return [
      'email.exists' => 'No active account found with this email address.',
      'password.strong_password' => 'Your password does not meet security requirements.'
    ];
  }
}
