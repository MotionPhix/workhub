<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PasswordResetService
{
  /**
   * Token validity duration (in minutes)
   */
  private const TOKEN_EXPIRATION = 10;

  /**
   * Generate a unique, time-limited reset token
   *
   * @param User $user
   * @return string
   */
  public function generateResetToken(User $user): string
  {
    // Delete any existing tokens for this user
    PasswordResetToken::where('user_id', $user->id)->delete();

    // Generate a cryptographically secure token
    $token = hash_hmac('sha256', Str::random(40), config('app.key'));

    // Create and store the token with expiration
    PasswordResetToken::create([
      'user_id' => $user->id,
      'token' => Hash::make($token),
      'created_at' => now(),
      'expires_at' => now()->addMinutes(self::TOKEN_EXPIRATION)
    ]);

    return $token;
  }

  /**
   * Validate reset token
   *
   * @param string $email
   * @param string $token
   * @return User|null
   */
  public function validateResetToken(string $email, string $token): ?User
  {
    // Find user by email
    $user = User::where('email', $email)->first();

    if (!$user) {
      return null;
    }

    // Find the token record
    $tokenRecord = PasswordResetToken::where('user_id', $user->id)
      ->first();

    // Check if token exists and is valid
    if (!$tokenRecord ||
      !Hash::check($token, $tokenRecord->token) ||
      $tokenRecord->expires_at->isPast()
    ) {
      return null;
    }

    return $user;
  }

  /**
   * Reset user password
   *
   * @param User $user
   * @param string $newPassword
   * @return bool
   */
  public function resetPassword(User $user, string $newPassword): bool
  {
    // Begin database transaction
    return DB::transaction(function () use ($user, $newPassword) {
      // Update user password
      $user->update([
        'password' => Hash::make($newPassword)
      ]);

      // Delete the used token
      PasswordResetToken::where('user_id', $user->id)->delete();

      // Log password reset
      ActivityLog::log(
        $user,
        'password_reset',
        'Password successfully reset',
        [
          'severity' => 'info',
          'metadata' => [
            'ip_address' => request()->ip()
          ]
        ]
      );

      return true;
    });
  }
}
