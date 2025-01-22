<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AuthenticationService
{
  /**
   * Maximum login attempts before lockout
   */
  private const MAX_LOGIN_ATTEMPTS = 5;

  /**
   * Lockout duration in minutes
   */
  private const LOCKOUT_DURATION = 15;

  public function __construct(
    protected PasswordResetService $passwordResetService
  ) {}

  public function login(array $credentials)
  {
    try {
      // Find user by email
      $user = User::where('email', $credentials['email'])->first();

      // Check if user exists
      if (!$user) {
        $this->handleFailedLogin($credentials['email']);
      }

      // Check account status
      $this->validateAccountStatus($user);

      // Validate password
      $this->validatePassword($user, $credentials['password']);

      // Check for potential brute force
      $this->checkLoginAttempts($user);

      // Perform login
      Auth::login($user, $credentials['remember'] ?? false);

      // Record successful login
      $this->recordSuccessfulLogin($user);

      // Generate authentication token
      $token = $this->generateAuthToken($user);

      return [
        'user' => $user,
        'token' => $token
      ];
    } catch (\Exception $e) {
      // Log the error
      Log::channel('user_management')->error('Login attempt failed', [
        'email' => $credentials['email'] ?? 'N/A',
        'error' => $e->getMessage(),
        'ip' => request()->ip()
      ]);

      // Rethrow or handle specific exceptions
      throw $e;
    }
  }

  /**
   * Handle failed login attempt
   *
   * @param string $email
   * @throws ValidationException
   */
  private function handleFailedLogin(string $email)
  {
    // Log failed login attempt
    ActivityLog::log(
      null,
      'login_failed',
      'Failed login attempt',
      [
        'severity' => 'warning',
        'metadata' => [
          'email' => $email,
          'ip_address' => request()->ip()
        ]
      ]
    );

    throw ValidationException::withMessages([
      'email' => ['The provided credentials are incorrect.'],
    ]);
  }

  /**
   * Validate account status
   *
   * @param User $user
   * @throws ValidationException
   */
  private function validateAccountStatus(User $user)
  {
    if (!$user->isAccountActive()) {
      ActivityLog::log(
        $user,
        'login blocked',
        'Login attempt on inactive account',
        [
          'severity' => 'warning',
          'metadata' => [
            'ip_address' => request()->ip()
          ]
        ]
      );

      throw ValidationException::withMessages([
        'email' => ['Your account is currently inactive.'],
      ]);
    }
  }

  /**
   * Validate user password
   *
   * @param User $user
   * @param string $password
   * @throws ValidationException
   */
  private function validatePassword(User $user, string $password)
  {
    if (!Hash::check($password, $user->password)) {
      $this->handleFailedLogin($user->email);
    }
  }

  /**
   * Check and manage login attempts
   *
   * @param User $user
   * @throws TooManyRequestsHttpException
   */
  private function checkLoginAttempts(User $user)
  {
    // Implement rate limiting logic
    $attempts = cache()->get("login_attempts_{$user->email}", 0);

    if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
      // Log account lockout
      ActivityLog::log(
        $user,
        'account locked',
        'Account temporarily locked due to multiple failed login attempts',
        [
          'severity' => 'critical',
          'metadata' => [
            'ip_address' => request()->ip()
          ]
        ]
      );

      // Throw HTTP exception for rate limiting
      throw new TooManyRequestsHttpException(
        self::LOCKOUT_DURATION * 60,
        'Too many login attempts. Please try again later.'
      );
    }
  }

  /**
   * Record successful login
   *
   * @param User $user
   */
  private function recordSuccessfulLogin(User $user)
  {
    // Clear login attempts cache
    cache()->forget("login_attempts_{$user->email}");

    // Record login details
    $user->recordLogin(request());

    // Log successful login
    ActivityLog::log(
      $user,
      'login',
      'User logged in successfully',
      [
        'severity' => 'info',
        'metadata' => [
          'ip_address' => request()->ip(),
          'user_agent' => request()->userAgent()
        ]
      ]
    );
  }

  /**
   * Generate authentication token
   *
   * @param User $user
   * @return string
   */
  private function generateAuthToken(User $user): string
  {
    return $user->createToken('auth_token', [
      'user:read',
      'user:write'
    ])->plainTextToken;
  }

  /**
   * Logout user
   */
  public function logout()
  {
    $user = request()->user();

    // Log logout activity
    ActivityLog::log(
      $user,
      'logout',
      'User logged out',
      [
        'severity' => 'info',
        'metadata' => [
          'ip_address' => request()->ip()
        ]
      ]
    );

    // Revoke current token
    $user->currentAccessToken()?->delete();

    Inertia::clearHistory();

    Auth::logout();
  }

  /**
   * Handle password reset status
   *
   * @param string $status
   * @throws ValidationException
   */
  private function handlePasswordResetStatus(string $status)
  {
    switch ($status) {
      case Password::PASSWORD_RESET:
        // Success is handled in the reset method
        break;
      case Password::INVALID_TOKEN:
        // Log invalid token attempt
        ActivityLog::log(
          null,
          'password_reset_failed',
          'Invalid password reset token used',
          [
            'severity' => 'warning',
            'metadata' => [
              'ip_address' => request()->ip()
            ]
          ]
        );

        throw ValidationException::withMessages([
          'token' => ['The password reset token is invalid or has expired.']
        ]);

      case Password::INVALID_USER:
        // Log attempt to reset password for non-existent user
        ActivityLog::log(
          null,
          'password_reset_failed',
          'Password reset attempted for non-existent user',
          [
            'severity' => 'warning',
            'metadata' => [
              'ip_address' => request()->ip(),
              'email' => request()->input('email')
            ]
          ]
        );

        throw ValidationException::withMessages([
          'email' => ['We cannot find a user with that email address.']
        ]);

      default:
        // Log unexpected password reset failure
        ActivityLog::log(
          null,
          'password_reset_error',
          'Unexpected error during password reset',
          [
            'severity' => 'critical',
            'metadata' => [
              'ip_address' => request()->ip(),
              'status' => $status
            ]
          ]
        );

        throw ValidationException::withMessages([
          'email' => ['Unable to reset password. Please try again.']
        ]);
    }
  }

  /**
   * Reset user password
   *
   * @param Request $request
   * @return void
   */
  public function resetPassword(Request $request)
  {
    $status = Password::reset(
      $request->only('email', 'password', 'password_confirmation', 'token'),
      function ($user) use ($request) {
        // Update password
        $user->forceFill([
          'password' => Hash::make($request->password)
        ])->save();

        // Log password reset
        ActivityLog::log(
          $user,
          'password_reset',
          'User successfully reset their password',
          [
            'severity' => 'info',
            'metadata' => [
              'ip_address' => $request->ip()
            ]
          ]
        );

        // Invalidate all existing tokens
        $user->tokens()->delete();

        // Notify user about password reset
        $user->notify(new PasswordResetNotification());
      }
    );

    // Handle password reset status
    $this->handlePasswordResetStatus($status);
  }

  /**
   * Generate a password reset link
   *
   * @param string $email
   * @return string
   * @throws ValidationException
   */
  public function generatePasswordResetLink(string $email): string
  {
    try {
      // Find user by email
      $user = User::where('email', $email)->first();

      if (!$user) {
        throw ValidationException::withMessages([
          'email' => ['We cannot find a user with that email address.']
        ]);
      }

      // Attempt to send reset link
      $status = Password::sendResetLink(['email' => $email]);

      // Generate reset token
      $token = $this->passwordResetService->generateResetToken($user);

      // Log reset link generation attempt
      ActivityLog::log(
        null,
        'password_reset_link_requested',
        'Password reset link generated',
        [
          'severity' => 'info',
          'metadata' => [
            'email' => $email,
            'ip_address' => request()->ip()
          ]
        ]
      );

      // Handle different statuses
      switch ($status) {
        case Password::RESET_LINK_SENT:
          return $status;

        case Password::INVALID_USER:
          // Log attempt for non-existent user
          ActivityLog::log(
            null,
            'password_reset_link_failed',
            'Password reset link requested for non-existent user',
            [
              'severity' => 'warning',
              'metadata' => [
                'email' => $email,
                'ip_address' => request()->ip()
              ]
            ]
          );

          throw ValidationException::withMessages([
            'email' => ['We cannot find a user with that email address.']
          ]);

        default:
          // Log unexpected error
          ActivityLog::log(
            null,
            'password_reset_link_error',
            'Unexpected error generating password reset link',
            [
              'severity' => 'critical',
              'metadata' => [
                'email' => $email,
                'status' => $status,
                'ip_address' => request()->ip()
              ]
            ]
          );

          throw ValidationException::withMessages([
            'email' => ['Unable to generate password reset link. Please try again.']
          ]);
      }
    } catch (\Exception $exception) {
      // Log any unexpected exceptions
      Log::channel('authentication')->error('Password reset link generation failed', [
        'email' => $email,
        'error' => $exception->getMessage(),
        'trace' => $exception->getTraceAsString()
      ]);

      throw ValidationException::withMessages([
        'email' => ['An unexpected error occurred. Please try again.']
      ]);
    }
  }

  /**
   * Verify two-factor authentication code
   *
   * @param User $user
   * @param string $code
   * @return bool
   */
  public function verifyTwoFactorCode(User $user, string $code): bool
  {
    try {
      // Implement two-factor verification logic
      $verified = $this->twoFactorService->verifyCode($user, $code);

      // Log verification attempt
      ActivityLog::log(
        $user,
        $verified ? 'two_factor_verified' : 'two_factor_failed',
        'Two-factor authentication verification',
        [
          'severity' => $verified ? 'info' : 'warning',
          'metadata' => [
            'ip_address' => request()->ip()
          ]
        ]
      );

      return $verified;
    } catch (\Exception $exception) {
      // Log verification error
      Log::channel('security')->error('Two-factor verification failed', [
        'user_id' => $user->id,
        'error' => $exception->getMessage()
      ]);

      return false;
    }
  }

  /**
   * Implement account lockout mechanism
   *
   * @param string $email
   */
  private function incrementLoginAttempts(string $email)
  {
    $attempts = cache()->get("login_attempts_{$email}", 0);
    $attempts++;

    // Store attempts with expiration
    cache()->put(
      "login_attempts_{$email}",
      $attempts,
      now()->addMinutes(self::LOCKOUT_DURATION)
    );

    // Log repeated failed attempts
    if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
      ActivityLog::log(
        null,
        'account_locked',
        'Multiple failed login attempts',
        [
          'severity' => 'critical',
          'metadata' => [
            'email' => $email,
            'ip_address' => request()->ip()
          ]
        ]
      );
    }
  }
}
