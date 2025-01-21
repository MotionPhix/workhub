<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TwoFactorAuthController extends Controller
{
  public function __construct(
    protected TwoFactorAuthService $twoFactorService
  ) {}

  public function showEnablePage()
  {
    $user = auth()->user();

    // Generate a new secret key
    $secretKey = $this->twoFactorService->generateSecretKey();

    // Generate QR Code SVG
    $qrCodeSvg = $this->twoFactorService->generateQRCodeSVG(
      $user,
      $secretKey
    );

    return Inertia('Auth/TwoFactor/Enable', [
      'secretKey' => $secretKey,
      'qrCodeSvg' => $qrCodeSvg
    ]);
  }

  public function enable(Request $request)
  {
    $request->validate([
      'secret_key' => 'required|string',
      'code' => 'required|numeric'
    ]);

    $user = auth()->user();

    // Attempt to enable two-factor authentication
    $enabled = $this->twoFactorService->enableTwoFactor(
      $user,
      $request->input('secret_key'),
      $request->input('code')
    );

    if (!$enabled) {
      return back()->with('error', 'Invalid verification code');
    }

    return redirect()->route('two-factor.recovery-codes')
      ->with('success', 'Two-factor authentication enabled');
  }

  public function disable(Request $request)
  {
    $request->validate([
      'password' => 'required|current_password'
    ]);

    $user = auth()->user();

    $disabled = $this->twoFactorService->disableTwoFactor(
      $user,
      $request->input('password')
    );

    if (!$disabled) {
      return back()->with('error', 'Invalid password');
    }

    return redirect()->route('profile')
      ->with('success', 'Two-factor authentication disabled');
  }

  public function showRecoveryCodes(Request $request)
  {
    $user = $request->user();

    // Decrypt and return recovery codes
    $recoveryCodes = json_decode(
      Crypt::decrypt($user->two_factor_recovery_codes),
      true
    );

    return Inertia('Auth/TwoFactor/RecoveryCodes', [
      'recoveryCodes' => $recoveryCodes
    ]);
  }

  public function regenerateRecoveryCodes()
  {
    $user = auth()->user();

    $newRecoveryCodes = $this->twoFactorService->regenerateRecoveryCodes($user);

    return redirect()->route('two-factor.recovery-codes')
      ->with('success', 'Recovery codes regenerated');
  }

  public function challenge(Request $request)
  {
    $request->validate([
      'code' => 'required|numeric'
    ]);

    $user = $request->user();

    // Decrypt the secret key
    $secretKey = Crypt::decrypt($user->two_factor_secret);

    // Verify the code
    if ($this->twoFactorService->verifyCode($secretKey, $request->input('code'))) {
      // Mark 2FA as passed for the session
      session(['2fa_passed' => true]);

      return redirect()->intended(route('dashboard'));
    }

    return back()->with('error', 'Invalid two-factor authentication code');
  }

  public function challengeWithRecoveryCode(Request $request)
  {
    $request->validate([
      'recovery_code' => 'required|string'
    ]);

    $user = auth()->user();

    // Verify recovery code
    if ($this->twoFactorService->verifyRecoveryCode($user, $request->input('recovery_code'))) {
      // Mark 2FA as passed for the session
      session(['2fa_passed' => true]);

      return redirect()->intended(route('dashboard'));
    }

    return back()->with('error', 'Invalid recovery code');
  }
}
