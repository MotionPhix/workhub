<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\AuthenticationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\ActivityLog;
use App\Rules\StrongPasswordRule;
use App\Services\AuthenticationService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
  public function __construct(
    protected AuthenticationService $authService,
    protected UserService           $userService
  ) {}

  public function showLoginForm()
  {
    return Inertia('auth/Login');
  }

  public function login(LoginRequest $request)
  {
    try {
      $result = $this->authService->login($request->validated());

      // Log login activity
      ActivityLog::log(
        $result['user'],
        'login',
        'User logged in successfully'
      );

      return redirect()->intended(route('profile.index'))
        ->with('status', 'Logged in successfully');
    } catch (AuthenticationException $e) {
      // Return validation-like errors to the frontend
      return back()
        ->withErrors($e->getErrors())
        ->withInput($request->only('email'));
    } catch (\Exception $e) {
      // Log error with file and line information
      ActivityLog::log(
        null,
        'login_failed',
        'Login attempt failed',
        [
          'severity' => 'error',
          'metadata' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
          ]
        ]
      );

      return back()->with('status', $e->getMessage());
    }
  }

  public function showRegistrationForm()
  {
    return Inertia('Auth/Register');
  }

  public function register(RegisterRequest $request)
  {
    try {
      $user = $this->userService->createUser($request->validated());

      return redirect()->route('login')
        ->with('flush', 'Account created successfully. Please login');
    } catch (\Exception $e) {
      return back()->with('error', $e->getMessage());
    }
  }

  public function logout(Request $request)
  {
    // Log logout activity
    ActivityLog::log(
      $request->user(),
      'logout',
      'User logged out'
    );

    $this->authService->logout();

    return redirect()->route('login')
      ->with('success', 'Logged out successfully');
  }

  public function forgotPassword()
  {
    return Inertia('Auth/ForgotPassword');
  }

  public function sendPasswordResetLink(Request $request)
  {
    try {
      $request->validate([
        'email' => 'required|email|exists:users,email'
      ]);

      // Use AuthenticationService to generate reset link
      $status = $this->authService->generatePasswordResetLink(
        $request->input('email')
      );

      // Return success response
      return back()
        ->with('status', __($status));
    } catch (ValidationException $e) {
      // Handle validation errors
      return back()
        ->withErrors($e->errors())
        ->withInput($request->only('email'));
    } catch (\Exception $e) {
      // Handle unexpected errors
      return back()
        ->with('error', $e->getMessage())
        ->withInput($request->only('email'));
    }
  }

  public function showResetPasswordForm(Request $request, $token)
  {
    return Inertia('Auth/ResetPassword', [
      'token' => $token,
      'email' => $request->email
    ]);
  }

  public function resetPassword(Request $request)
  {
    try {
      $request->validate([
        'token' => 'required',
        'email' => 'required|email,exists:users',
        'password' => [
          'required',
          'confirmed',
          new StrongPasswordRule()
        ]
      ]);

      // Use the AuthenticationService to reset password
      $this->authService->resetPassword($request);

      // Redirect to login with success message
      return redirect()
        ->route('login')
        ->with('status', 'Your password has been reset successfully.');
    } catch (ValidationException $e) {
      // Handle validation errors (e.g., invalid token, email)
      return back()
        ->withErrors($e->errors())
        ->withInput($request->except('password', 'password_confirmation'));
    } catch (\Exception $e) {
      // Handle any unexpected errors
      return back()
        ->with('error', $e->getMessage())
        ->withInput($request->except('password', 'password_confirmation'));
    }
  }

  public function verifyEmail(Request $request)
  {
    $user = $request->user();

    if ($user->hasVerifiedEmail()) {
      return redirect()->intended(route('dashboard'));
    }

    $user->sendEmailVerificationNotification();

    return back()->with('status', 'Verification link sent');
  }

  public function confirmEmailVerification(Request $request)
  {
    $user = $request->user();

    if ($user->markEmailAsVerified()) {
      // Log email verification
      ActivityLog::log(
        $user,
        'email_verification',
        'User verified their email'
      );
    }

    return redirect()->intended(route('dashboard'));
  }

  public function updateAvatar(Request $request)
  {
    $request->validate([
      'avatar' => [
        'required',
        'image',
        'max:10240', // 10MB
        'mimes:jpeg,png,gif,webp'
      ]
    ]);

    $user = $request->user();

    try {
      $this->userService->updateUserProfile($user, [
        'avatar' => $request->file('avatar')
      ]);

      return back()->with('success', 'Avatar updated successfully');
    } catch (\Exception $e) {
      return back()->with('error', 'Failed to update avatar');
    }
  }
}
