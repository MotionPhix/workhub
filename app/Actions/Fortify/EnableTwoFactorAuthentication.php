<?php

namespace App\Actions\Fortify;

use App\Services\ActivityLogger;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication as BaseTwoFactorAuthentication;

class EnableTwoFactorAuthentication extends BaseTwoFactorAuthentication
{
  public function __invoke($user, $force = false)
  {
    // Custom logic before enabling 2FA
    parent::__invoke($user);

    // Log 2FA activation
    ActivityLogger::logWorkEntryAction('enabled two-factor authentication');
  }
}
