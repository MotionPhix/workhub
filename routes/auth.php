<?php

use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ProductivityPredictionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get(
        'register',
        [\App\Http\Controllers\Auth\AuthController::class, 'showRegistrationForm']
    )->name('register');

    Route::post(
        'register',
        [\App\Http\Controllers\Auth\AuthController::class, 'register']
    )->middleware('rate.limit:registration');

    Route::get(
        'login',
        [\App\Http\Controllers\Auth\AuthController::class, 'showLoginForm']
    )->name('login');

    Route::post(
        'login',
        [\App\Http\Controllers\Auth\AuthController::class, 'login']
    )->middleware('rate.limit:login');

    // Invitation Routes (Guest Access)
    Route::get('invitation/{token}', [\App\Http\Controllers\Auth\InvitationAcceptanceController::class, 'show'])->name('invitation.show');
    Route::post('invitation/{token}/accept', [\App\Http\Controllers\Auth\InvitationAcceptanceController::class, 'accept'])->name('invitation.accept');
    Route::post('invitation/{token}/decline', [\App\Http\Controllers\Auth\InvitationAcceptanceController::class, 'decline'])->name('invitation.decline');

    Route::get(
        'forgot-password',
        [\App\Http\Controllers\Auth\AuthController::class, 'forgotPassword']
    )->name('password.request');

    Route::post(
        'forgot-password',
        [\App\Http\Controllers\Auth\AuthController::class, 'sendPasswordResetLink']
    )->name('password.email');

    Route::get(
        'reset-password/{token}',
        [\App\Http\Controllers\Auth\AuthController::class, 'showResetPasswordForm']
    )->name('password.reset');

    Route::post(
        'reset-password',
        [\App\Http\Controllers\Auth\AuthController::class, 'resetPassword']
    )->middleware('rate.limit:password_reset')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    // Two-Factor Authentication Routes
    Route::get(
        '/two-factor',
        [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'showEnablePage']
    )->name('two-factor.enable');

    Route::post(
        '/two-factor/enable',
        [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'enable']
    )->name('two-factor.store');

    Route::post(
        '/two-factor/disable',
        [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'disable']
    )->name('two-factor.disable');

    // Recovery Codes Management
    Route::get(
        '/two-factor/recovery-codes',
        [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'showRecoveryCodes']
    )->name('two-factor.recovery-codes');

    Route::post(
        '/two-factor/recovery-codes/regenerate',
        [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'regenerateRecoveryCodes']
    )->name('two-factor.recovery-codes.regenerate');

    // Two-Factor Challenge Routes
    Route::middleware('2fa.required')->group(function () {
        Route::get(
            '/two-factor/challenge',
            [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'showChallengePage']
        )->name('two-factor.challenge');

        Route::post(
            '/two-factor/challenge',
            [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'challenge']
        )->name('two-factor.verify');

        Route::post(
            '/two-factor/recovery',
            [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'challengeWithRecoveryCode']
        )->name('two-factor.verify-recovery');
    });

    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get(
        'verify-email/{id}/{hash}',
        VerifyEmailController::class
    )->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post(
        'email/verification-notification',
        [EmailVerificationNotificationController::class, 'store']
    )
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get(
        'confirm-password',
        [ConfirmablePasswordController::class, 'show']
    )->name('password.confirm');

    Route::post(
        'confirm-password',
        [ConfirmablePasswordController::class, 'store']
    );

    Route::put(
        'password',
        [PasswordController::class, 'update']
    )->name('password.update');

    Route::post(
        'logout',
        [\App\Http\Controllers\Auth\AuthController::class, 'logout']
    )->name('logout');

    /* >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> */

    //  Route::middleware([
    //    'role:employee|manager|admin'
    //  ])->group(function () {

    Route::get(
        '/',
        [\App\Http\Controllers\DashboardController::class, 'index']
    )->name('dashboard');

    Route::prefix('onboarding')->group(function () {

        Route::post(
            '/complete-step',
            \App\Http\Controllers\Onboarding\CompleteStep::class,
        )->name('onboarding.complete-step');

        Route::get(
            '/status',
            \App\Http\Controllers\Onboarding\Status::class,
        )->name('onboarding.status');

    });

    Route::prefix('work-logs')->name('work-entries.')->group(function () {

        Route::get(
            '/',
            [\App\Http\Controllers\Work\WorkEntryController::class, 'index'],
        )->name('index');

        Route::get(
            '/new-work-log',
            [\App\Http\Controllers\Work\WorkEntryController::class, 'form'],
        )->name('create');

        Route::get(
            '/e/{entry:uuid}',
            [\App\Http\Controllers\Work\WorkEntryController::class, 'form'],
        )->name('edit');

        Route::get(
            '/s/{entry:uuid}',
            [\App\Http\Controllers\Work\WorkEntryController::class, 'show'],
        )->name('show');

        Route::post(
            '/',
            [\App\Http\Controllers\Work\WorkEntryController::class, 'store'],
        )->name('store');

        Route::put(
            '/u/{entry:uuid}',
            [\App\Http\Controllers\Work\WorkEntryController::class, 'update'],
        )->name('update');

        Route::delete(
            '/d/{entry:uuid}',
            [\App\Http\Controllers\Work\WorkEntryController::class, 'destroy'],
        )->name('destroy');

    });

    Route::get(
        '/productivity/predictions/{userId}',
        [ProductivityPredictionController::class, 'getPredictions']
    )->name('productivity.predictions');

    Route::prefix('profile')->name('profile.')->group(function () {

        Route::get('/{user:uuid}', [UserController::class, 'profile'])->name('index');
        Route::get('/e/{user:uuid}', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/u', [UserController::class, 'update'])->name('update');
        Route::delete('/d', [ProfileController::class, 'destroy'])->name('destroy');

        Route::put('/u/settings', [UserController::class, 'updateSettings'])->name('settings');

    });

    //  });

    //  Route::middleware([
    //    'role:manager|admin'
    //  ])->group(function () {

    Route::get(
        '/reports',
        [\App\Http\Controllers\Report\ReportsController::class, 'index']
    )->name('reports.index');

    // Admin Routes for Invitations
    Route::middleware(['can:manage-invitations'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('invitations', \App\Http\Controllers\Admin\InvitationController::class);
        Route::post('invitations/{invitation}/resend', [\App\Http\Controllers\Admin\InvitationController::class, 'resend'])->name('invitations.resend');
        Route::post('invitations/{invitation}/cancel', [\App\Http\Controllers\Admin\InvitationController::class, 'cancel'])->name('invitations.cancel');
        Route::post('invitations/bulk', [\App\Http\Controllers\Admin\InvitationController::class, 'bulkInvite'])->name('invitations.bulk');
    });

    //  });
});
