<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuth
{
  public function handle(Request $request, Closure $next): Response
  {
    $user = $request->user();

    // Check if user is authenticated
    if (!$user) {
      return redirect()->route('login');
    }

    // Check if two-factor is enabled
    if ($user->two_factor_secret && !session('2fa_passed')) {
      return redirect()->route('two-factor.challenge');
    }

    return $next($request);
  }
}
