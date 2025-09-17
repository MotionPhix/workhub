<?php

use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
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
