<?php

use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| These routes handle all authentication-related functionality including
| login, logout, password resets, email verification, and two-factor auth.
|
*/

Route::middleware(['guest', 'redirect.after.login'])->group(function () {

    // Login Routes
    Route::get('login', [\App\Http\Controllers\Auth\AuthController::class, 'showLoginForm'])
        ->name('login');
    Route::post('login', [\App\Http\Controllers\Auth\AuthController::class, 'login'])
        ->middleware('rate.limit:login');

    // Invitation Routes (Guest Access)
    Route::get('invitation/{token}', [\App\Http\Controllers\Auth\InvitationAcceptanceController::class, 'show'])
        ->name('invitation.show');
    Route::post('invitation/{token}/accept', [\App\Http\Controllers\Auth\InvitationAcceptanceController::class, 'accept'])
        ->name('invitation.accept');
    Route::post('invitation/{token}/decline', [\App\Http\Controllers\Auth\InvitationAcceptanceController::class, 'decline'])
        ->name('invitation.decline');

    // Password Reset Routes
    Route::get('forgot-password', [\App\Http\Controllers\Auth\AuthController::class, 'forgotPassword'])
        ->name('password.request');
    Route::post('forgot-password', [\App\Http\Controllers\Auth\AuthController::class, 'sendPasswordResetLink'])
        ->name('password.email');
    Route::get('reset-password/{token}', [\App\Http\Controllers\Auth\AuthController::class, 'showResetPasswordForm'])
        ->name('password.reset');
    Route::post('reset-password', [\App\Http\Controllers\Auth\AuthController::class, 'resetPassword'])
        ->middleware('rate.limit:password_reset')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {

    // Logout
    Route::post('logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])
        ->name('logout');

    // Email Verification Routes
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Password Confirmation
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Password Update
    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');

    // Two-Factor Authentication Routes
    Route::get('/two-factor', [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'showEnablePage'])
        ->name('two-factor.enable');
    Route::post('/two-factor/enable', [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'enable'])
        ->name('two-factor.store');
    Route::post('/two-factor/disable', [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'disable'])
        ->name('two-factor.disable');

    // Recovery Codes Management
    Route::get('/two-factor/recovery-codes', [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'showRecoveryCodes'])
        ->name('two-factor.recovery-codes');
    Route::post('/two-factor/recovery-codes/regenerate', [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'regenerateRecoveryCodes'])
        ->name('two-factor.recovery-codes.regenerate');

    // Two-Factor Challenge Routes
    Route::middleware('2fa.required')->group(function () {
        Route::get('/two-factor/challenge', [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'showChallengePage'])
            ->name('two-factor.challenge');
        Route::post('/two-factor/challenge', [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'challenge'])
            ->name('two-factor.verify');
        Route::post('/two-factor/recovery', [\App\Http\Controllers\Auth\TwoFactorAuthController::class, 'challengeWithRecoveryCode'])
            ->name('two-factor.verify-recovery');
    });
});
