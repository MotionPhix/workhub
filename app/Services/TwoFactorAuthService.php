<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
  protected $google2fa;

  public function __construct(Google2FA $google2fa)
  {
    $this->google2fa = $google2fa;
  }

  public function generateSecretKey(): string
  {
    return $this->google2fa->generateSecretKey();
  }

  public function generateQRCodeSVG(User $user, string $secretKey): string
  {
    $companyName = config('app.name');
    $qrCodeUrl = $this->google2fa->getQRCodeUrl(
      $companyName,
      $user->email,
      $secretKey
    );

    $renderer = new ImageRenderer(
      new RendererStyle(300),
      new SvgImageBackEnd()
    );
    $writer = new Writer($renderer);

    return $writer->writeString($qrCodeUrl);
  }

  public function enableTwoFactor(User $user, string $secretKey, string $code): bool
  {
    // Verify the code
    if (!$this->verifyCode($secretKey, $code)) {
      return false;
    }

    // Generate recovery codes
    $recoveryCodes = $this->generateRecoveryCodes();

    // Encrypt and store secret key and recovery codes
    $user->update([
      'two_factor_secret' => Crypt::encrypt($secretKey),
      'two_factor_recovery_codes' => Crypt::encrypt(json_encode($recoveryCodes)),
      'two_factor_confirmed_at' => now()
    ]);

    return true;
  }

  public function disableTwoFactor(User $user, string $password): bool
  {
    // Verify user password before disabling
    if (!Hash::check($password, $user->password)) {
      return false;
    }

    $user->update([
      'two_factor_secret' => null,
      'two_factor_recovery_codes' => null,
      'two_factor_confirmed_at' => null
    ]);

    return true;
  }

  public function verifyCode(string $secretKey, string $code): bool
  {
    return $this->google2fa->verifyKey($secretKey, $code);
  }

  public function verifyRecoveryCode(User $user, string $recoveryCode): bool
  {
    $recoveryCodes = json_decode(
      Crypt::decrypt($user->two_factor_recovery_codes),
      true
    );

    $index = array_search($recoveryCode, $recoveryCodes);

    if ($index !== false) {
      // Remove used recovery code
      unset($recoveryCodes[$index]);

      $user->update([
        'two_factor_recovery_codes' => Crypt::encrypt(
          json_encode(array_values($recoveryCodes))
        )
      ]);

      return true;
    }

    return false;
  }

  protected function generateRecoveryCodes(): array
  {
    return collect(range(1, 8))
      ->map(fn() => strtoupper(Str::random(10)))
      ->toArray();
  }

  public function regenerateRecoveryCodes(User $user): array
  {
    $newRecoveryCodes = $this->generateRecoveryCodes();

    $user->update([
      'two_factor_recovery_codes' => Crypt::encrypt(
        json_encode($newRecoveryCodes)
      )
    ]);

    return $newRecoveryCodes;
  }
}
