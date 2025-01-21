<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\User\AccountCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserService
{
  public function createUser(array $data): User
  {
    try {
      return DB::transaction(function () use ($data) {
        // Create user
        $user = User::create([
          'name' => $data['name'],
          'email' => $data['email'],
          'password' => Hash::make($data['password']),
          'department' => $data['department'] ?? null,
          'manager_email' => $data['manager_email'] ?? null,
        ]);

        // Assign default role
        $user->syncRoles('employee');

        // Send welcome notification
        $this->sendWelcomeNotification($user);

        // Log user creation
        $this->logUserCreation($user);

        return $user;
      });
    } catch (\Exception $exception) {
      // Log detailed error information
      $this->logUserCreationError($exception, $data);

      // Rethrow the exception to be handled by the controller
      throw $exception;
    }
  }

  /**
   * Update user profile with media handling
   *
   * @param User $user
   * @param array $data
   * @return User
   */
  public function updateUserProfile(User $user, array $data): User
  {
    try {
      return DB::transaction(function () use ($user, $data) {
        // Handle avatar upload
        if (isset($data['avatar'])) {
          $this->handleAvatarUpload($user, $data['avatar']);
        }

        // Update other user fields
        $user->update(collect($data)->except('avatar')->toArray());

        // Log profile update
        $this->logProfileUpdate($user, $data);

        return $user;
      });
    } catch (\Exception $exception) {
      // Log error details
      Log::channel('user_management')->error('User profile update failed', [
        'user_id' => $user->id,
        'error' => $exception->getMessage(),
      ]);

      throw $exception;
    }
  }

  /**
   * Handle avatar upload using Media Library
   *
   * @param User $user
   * @param mixed $avatarFile
   */
  private function handleAvatarUpload(User $user, $avatarFile): void
  {
    // Validate file
    if (!$avatarFile || !$avatarFile->isValid()) {
      throw new \InvalidArgumentException('Invalid avatar file');
    }

    // Set new avatar
    $user->setAvatar($avatarFile);
  }

  /**
   * Log profile update
   *
   * @param User $user
   * @param array $data
   */
  private function logProfileUpdate(User $user, array $data): void
  {
    ActivityLog::log(
      $user,
      'profile_updated',
      'User profile updated',
      $user
    );

    Log::channel('user_management')->info('Profile updated', [
      'user_id' => $user->id,
      'updated_fields' => array_keys($data),
    ]);
  }

  /**
   * Change user password
   *
   * @param User $user
   * @param string $newPassword
   * @return bool
   * @throws \Exception
   */
  public function changeUserPassword(User $user, string $newPassword): bool
  {
    try {
      return DB::transaction(function () use ($user, $newPassword) {
        $user->update([
          'password' => Hash::make($newPassword)
        ]);

        // Invalidate all existing tokens
        $user->tokens()->delete();

        // Log password change
        $this->logPasswordChange($user);

        return true;
      });
    } catch (\Exception $exception) {
      // Log error details
      Log::channel('security')->error('Password change failed', [
        'user_id' => $user->id,
        'error' => $exception->getMessage(),
        'trace' => $exception->getTraceAsString(),
      ]);

      // Rethrow the exception
      throw $exception;
    }
  }

  /**
   * Log password change
   *
   * @param User $user
   */
  private function logPasswordChange(User $user): void
  {
    ActivityLog::log(
      $user,
      'password_changed',
      'User password was changed'
    );

    Log::channel('security')->info('Password changed', [
      'user_id' => $user->id,
    ]);
  }

  public function generateTwoFactorRecoveryCodes(User $user): array
  {
    $recoveryCodes = collect(range(1, 8))
      ->map(fn() => Str::random(10))
      ->toArray();

    $user->update([
      'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes))
    ]);

    return $recoveryCodes;
  }

  /**
   * Send welcome notification to the newly created user
   *
   * @param User $user
   */
  private function sendWelcomeNotification(User $user): void
  {
    try {
      $user->notify(new AccountCreated($user));
    } catch (\Exception $exception) {
      Log::channel('notifications')->error('Welcome notification failed', [
        'user_id' => $user->id,
        'error' => $exception->getMessage(),
      ]);
    }
  }

  /**
   * Log user creation details
   *
   * @param User $user
   */
  private function logUserCreation(User $user): void
  {
    ActivityLog::log(
      $user,
      'user_created',
      'New user account created',
      $user,
    );

    Log::channel('user_management')->info('User created', [
      'user_id' => $user->id,
      'email' => $user->email,
    ]);
  }

  /**
   * Log detailed error information for user creation failure
   *
   * @param \Exception $exception
   * @param array $data
   */
  private function logUserCreationError(\Exception $exception, array $data): void
  {
    Log::channel('user_management')->critical('User creation failed', [
      'error' => $exception->getMessage(),
      'trace' => $exception->getTraceAsString(),
      'input' => $this->sanitizeLogData($data),
    ]);
  }

  /**
   * Sanitize data for logging (remove sensitive information)
   *
   * @param array $data
   * @return array
   */
  private function sanitizeLogData(array $data): array
  {
    $sensitiveFields = ['password', 'password_confirmation'];

    return collect($data)
      ->except($sensitiveFields)
      ->toArray();
  }

  /**
   * Filter update data to prevent updating sensitive fields
   *
   * @param array $data
   * @return array
   */
  private function filterUpdateData(array $data): array
  {
    $allowedFields = [
      'name',
      'email',
      'department',
      'avatar'
    ];

    return collect($data)
      ->only($allowedFields)
      ->toArray();
  }
}
