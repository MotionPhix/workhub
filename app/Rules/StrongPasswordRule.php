<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class StrongPasswordRule implements ValidationRule
{
  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    $config = config('password.requirements');

    // Check minimum length
    if (
      Str::length($value) < $config['min_length'] ||
      Str::length($value) > $config['max_length']
    ) {
      $fail("The password must be between {$config['min_length']} and {$config['max_length']} characters.");
    }

    // Complexity checks
    $checks = [
      'letters' => fn($v) => preg_match('/[a-zA-Z]/', $v),
      'mixed_case' => fn($v) => preg_match('/[a-z]/', $v) && preg_match('/[A-Z]/', $v),
      'numbers' => fn($v) => preg_match('/[0-9]/', $v),
      'symbols' => fn($v) => preg_match('/[!@#$%^&*(),.?":{}|<>]/', $v),
    ];

    // Check for lowercase letters
    if (!preg_match('/[a-z]/', $value)) {
      $fail('The password must contain at least one lowercase letter.');
    }

    // Check for uppercase letters
    if (!preg_match('/[A-Z]/', $value)) {
      $fail('The password must contain at least one uppercase letter.');
    }

    // Check for numbers
    if (!preg_match('/[0-9]/', $value)) {
      $fail('The password must contain at least one number.');
    }

    // Check for special characters
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
      $fail('The password must contain at least one special character.');
    }

    // Consecutive characters check
    if ($this->hasConsecutiveCharacters($value, $config['consecutive_characters_limit'])) {
      $fail("Password cannot have more than {$config['consecutive_characters_limit']} consecutive characters.");
    }

    foreach ($checks as $check => $validator) {
      if ($config['complexity'][$check] && !$validator($value)) {
        $fail($this->getFailureMessage($check));
      }
    }

    // Optional: Check against common passwords or known breached passwords
    $this->checkAgainstBreachedPasswords($value, $fail);
  }

  protected function checkAgainstBreachedPasswords(string $password, Closure $fail)
  {
    // Implement integration with HaveIBeenPwned API or similar service
    // This is a placeholder for actual implementation
    $breachedPasswords = [
      'password123',
      'qwerty123',
      // Add more common breached passwords
    ];

    if (in_array(strtolower($password), $breachedPasswords)) {
      $fail('This password has been compromised and cannot be used.');
    }
  }

  protected function getFailureMessage(string $check): string
  {
    return match($check) {
      'letters' => 'Password must contain letters.',
      'mixed_case' => 'Password must contain both uppercase and lowercase letters.',
      'numbers' => 'Password must contain numbers.',
      'symbols' => 'Password must contain special characters.',
      default => 'Password does not meet complexity requirements.'
    };
  }

  protected function hasConsecutiveCharacters(string $password, int $limit): bool
  {
    return preg_match("/(.)\1{" . ($limit - 1) . ",}/", $password);
  }
}
