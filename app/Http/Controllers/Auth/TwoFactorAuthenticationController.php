<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

class TwoFactorAuthenticationController extends Controller
{
  public function enable(Request $request, EnableTwoFactorAuthentication $enabler)
  {
    if (!Features::optionEnabled(Features::twoFactorAuthentication())) {
      return back()->with('error', 'Two-factor authentication is not enabled.');
    }

    $enabler($request->user());

    return back()->with('status', 'Two-factor authentication has been enabled.');
  }

  public function disable(Request $request, DisableTwoFactorAuthentication $disabler)
  {
    $disabler($request->user());

    return back()->with('status', 'Two-factor authentication has been disabled.');
  }

  public function showRecoveryCodes(Request $request)
  {
    if (!$request->user()->two_factor_secret) {
      return back()->with('error ', 'You have not enabled two-factor authentication.');
    }

    return view('auth.recovery-codes', [
      'codes' => $request->user()->recoveryCodes(),
    ]);
  }
}
