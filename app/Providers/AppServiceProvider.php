<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
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
        Fortify::loginView(fn () => Inertia('auth/Login'));
        // Registration is invitation-only, so regular registration views are disabled
        // Fortify::registerView(fn () => Inertia('auth/Register'));
        Fortify::requestPasswordResetLinkView(fn () => Inertia('auth/ForgotPassword'));
        Fortify::resetPasswordView(fn ($request) => Inertia('auth/ResetPassword', [
            'token' => $request->token,
            'email' => $request->email,
        ]));

        // Define authorization gates
        Gate::define('manage-invitations', function (User $user) {
            return $user->hasRole(['admin', 'manager']);
        });

        // Manager dashboard access
        Gate::define('access-manager-dashboard', function (User $user) {
            return $user->hasRole('manager');
        });

        // Admin panel access
        Gate::define('access-admin-panel', function (User $user) {
            return $user->hasRole('admin');
        });

        // Manager permissions
        Gate::define('view-team-reports', function (User $user) {
            return $user->hasRole(['manager', 'admin']);
        });

        Gate::define('view-team-performance', function (User $user) {
            return $user->hasRole(['manager', 'admin']);
        });

        Gate::define('manage-team-schedules', function (User $user) {
            return $user->hasRole(['manager', 'admin']);
        });

        Gate::define('export-team-analytics', function (User $user) {
            return $user->hasRole(['manager', 'admin']);
        });

        Gate::define('bulk-approve-reports', function (User $user) {
            return $user->hasRole(['manager', 'admin']);
        });

        Gate::define('view-team-members', function (User $user) {
            return $user->hasRole(['manager', 'admin']);
        });

        Gate::define('manage-team-members', function (User $user) {
            return $user->hasRole(['manager', 'admin']);
        });

        Gate::define('view-team-work-entries', function (User $user) {
            return $user->hasRole(['manager', 'admin']);
        });

        Gate::define('approve-work-entries', function (User $user) {
            return $user->hasRole(['manager', 'admin']);
        });

        Gate::define('view-team-insights', function (User $user) {
            return $user->hasRole(['manager', 'admin']);
        });
    }
}
