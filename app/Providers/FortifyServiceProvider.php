<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    Fortify::ignoreRoutes();
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    Fortify::createUsersUsing(CreateNewUser::class);
    Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
    Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
    Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

    // Custom views for authentication
    Fortify::loginView(fn() => inertia('auth/Login'));
    // Registration is invitation-only, so regular registration views are disabled
    // Fortify::registerView(fn() => inertia('auth/Register'));

    Fortify::requestPasswordResetLinkView(
      fn() => inertia('auth/ForgotPassword')
    );

    Fortify::resetPasswordView(
      fn($request) => inertia('auth/ResetPassword', [
        'request' => $request
      ])
    );
    Fortify::verifyEmailView(
      fn() => inertia('auth/VerifyEmail')
    );

    Fortify::confirmPasswordView(
      fn() => inertia('auth/ConfirmPassword')
    );

    RateLimiter::for('login', function (Request $request) {
      $throttleKey = Str::transliterate(
        Str::lower($request->input(Fortify::username())) . '|' . $request->ip()
      );

      return Limit::perMinute(5)->by($throttleKey);
    });

    RateLimiter::for('two-factor', function (Request $request) {
      return Limit::perMinute(5)
        ->by($request->session()
          ->get('login.id'));
    });
  }
}
