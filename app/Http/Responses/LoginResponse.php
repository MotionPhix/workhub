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

    // Redirect to intended page or dashboard
    return redirect()->intended(route('dashboard'));
  }
}
