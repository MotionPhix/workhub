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
| login, logout, password resets, and email verification.
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
});
