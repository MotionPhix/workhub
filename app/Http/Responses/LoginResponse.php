<?php

namespace App\Http\Responses;

use App\Models\ActivityLog;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
  public function toResponse($request)
  {
    $user = $request->user();

    // Check if two-factor is enabled and not yet verified
    if ($user->two_factor_secret && !session('2fa_passed')) {
      return redirect()->route('two-factor.challenge');
    }

    // Log successful login
    ActivityLog::log(
      $user,
      'login',
      'User logged in successfully'
    );

    // Determine redirect based on user role
    $redirectUrl = $this->getRedirectUrlByRole($user);

    // Redirect to intended page or role-based dashboard
    return redirect()->intended($redirectUrl);
  }

  /**
   * Get the appropriate redirect URL based on user role
   */
  private function getRedirectUrlByRole($user): string
  {
    if ($user->hasRole('admin')) {
      return route('admin.dashboard');
    }

    if ($user->hasRole('manager')) {
      return route('manager.dashboard');
    }

    // Default to employee dashboard
    return route('dashboard');
  }
}
