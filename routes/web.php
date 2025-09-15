<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductivityPredictionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkEntryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Member Web Routes
|--------------------------------------------------------------------------
|
| These routes are for authenticated members (employees) of the application.
| All routes should use UUIDs for resource identification.
|
*/

// Include authentication routes
require __DIR__.'/auth.php';

// Include admin routes
require __DIR__.'/admin.php';

// Include manager routes
require __DIR__.'/manager.php';

Route::middleware(['auth', 'role.access'])->group(function () {

    // Member Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Member Onboarding (if applicable - e.g., first login, profile incomplete, etc.)
    // Route::get('/onboarding', [\App\Http\Controllers\Onboarding::class, 'index'])->name('onboarding');

    // Work Entries (Member's own work logs)
    Route::prefix('work-logs')->name('work-entries.')->group(function () {
        Route::get('/', [WorkEntryController::class, 'index'])->name('index');
        Route::get('/new-work-log', [WorkEntryController::class, 'form'])->name('create');
        Route::get('/e/{entry:uuid}', [WorkEntryController::class, 'form'])->name('edit');
        Route::get('/s/{entry:uuid}', [WorkEntryController::class, 'show'])->name('show');
        Route::post('/', [WorkEntryController::class, 'store'])->name('store');
        Route::put('/u/{entry:uuid}', [WorkEntryController::class, 'update'])->name('update');
        Route::delete('/d/{entry:uuid}', [WorkEntryController::class, 'destroy'])->name('destroy');
    });

    // Member Profile Management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/{user:uuid}', [UserController::class, 'profile'])->name('index');
        Route::get('/e/{user:uuid}', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/u', [UserController::class, 'update'])->name('update');
        Route::delete('/d', [ProfileController::class, 'destroy'])->name('destroy');
        Route::put('/u/settings', [UserController::class, 'updateSettings'])->name('settings');
    });

    // Member Productivity Data (personal)
    Route::get('/productivity/predictions/{user:uuid}', [ProductivityPredictionController::class, 'getPredictions'])
        ->name('productivity.predictions');

    // Member Reports (personal reports only)
    Route::middleware(['can:view-own-reports'])->group(function () {
        Route::get('/reports', [\App\Http\Controllers\ReportsController::class, 'memberIndex'])
            ->name('reports.index');
        Route::get('/reports/{report:uuid}', [\App\Http\Controllers\ReportsController::class, 'memberShow'])
            ->name('reports.show');
    });
});
