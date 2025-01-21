<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    Vite::prefetch(concurrency: 3);

    // Custom password validation
    Validator::extend('strong_password', function ($attribute, $value, $parameters, $validator) {
      return Password::min(12)
        ->letters()
        ->mixedCase()
        ->numbers()
        ->symbols()
        ->uncompromised()
        ->validate($value);
    });

    // Customize password reset link
    ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
      return route('password.reset', [
        'token' => $token,
        'email' => $notifiable->getEmailForPasswordReset(),
      ]);
    });

    // Customize email verification
    VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
      return (new MailMessage)
        ->subject('Verify Email Address')
        ->line('Click the button below to verify your email address.')
        ->action('Verify Email Address', $url)
        ->line('If you did not create an account, no further action is required.');
    });

    // Fortify authentication configurations
    Fortify::authenticateUsing(function ($request) {
      $user = User::where('email', $request->email)->first();

      if ($user &&
        Hash::check($request->password, $user->password) &&
        $user->isAccountActive()
      ) {
        return $user;
      }

      return null;
    });

    // Customize views (optional, as Inertia handles this differently)
    Fortify::loginView(fn () => Inertia('Auth/Login'));
    Fortify::registerView(fn () => Inertia('Auth/Register'));
    Fortify::requestPasswordResetLinkView(fn () => Inertia('Auth/ForgotPassword'));
    Fortify::resetPasswordView(fn ($request) => Inertia('Auth/ResetPassword', [
      'token' => $request->token,
      'email' => $request->email
    ]));
  }
}
