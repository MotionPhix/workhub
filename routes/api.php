<?php

use App\Http\Controllers\ReportsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Notification API routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/', [NotificationController::class, 'clear'])->name('notifications.clear');
        Route::get('/statistics', [NotificationController::class, 'statistics']);
    });

    // Notification preferences API routes
    Route::prefix('notification-preferences')->group(function () {
        Route::get('/', [NotificationPreferenceController::class, 'index']);
        Route::post('/', [NotificationPreferenceController::class, 'update']);
        Route::post('/reset', [NotificationPreferenceController::class, 'reset']);
    });
    Route::get(
        '/export',
        [ReportsController::class, 'export']
    )->name('api.reports.export');

    Route::get(
        '/reports/dashboard',
        [ReportsController::class, 'dashboard']
    )->name('api.reports.dashboard');

    Route::get(
        '/insights/{user:uuid}',
        \App\Http\Controllers\Api\Insights\Index::class
    )->name('api.user.insights');

    Route::get(
        '/work-logs/{user:uuid}',
        \App\Http\Controllers\Api\WorkLogs\Latest::class
    )->name('api.user.work-logs');
});
