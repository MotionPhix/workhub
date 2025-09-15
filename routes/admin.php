<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| These routes are for admin users only and are prefixed with 'admin'
| All routes in this file should use UUIDs for resource identification
| and require appropriate admin permissions.
|
*/

Route::middleware(['auth', 'can:access-admin-panel', 'role.access'])->prefix('admin')->name('admin.')->group(function () {

    // Admin Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Admin Profile Management
    Route::get('profile', [AdminDashboardController::class, 'profile'])->name('profile.index');
    Route::get('profile/settings', [AdminDashboardController::class, 'settings'])->name('profile.settings');
    Route::put('profile/settings', [AdminDashboardController::class, 'updateSettings'])->name('profile.settings.update');

    // Invitations Management
    Route::middleware(['can:view-invitations'])->group(function () {
        Route::resource('invitations', InvitationController::class)->parameters([
            'invitations' => 'invitation:uuid',
        ]);
        Route::post('invitations/{invitation:uuid}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
        Route::post('invitations/{invitation:uuid}/cancel', [InvitationController::class, 'cancel'])->name('invitations.cancel');
        Route::post('invitations/bulk', [InvitationController::class, 'bulkInvite'])->name('invitations.bulk');
    });

    // Reports & Analytics (Admin oversight)
    Route::middleware(['can:view-system-reports'])->group(function () {
        Route::get('reports', [ReportsController::class, 'adminIndex'])->name('reports.index');
        Route::get('reports/{report:uuid}', [ReportsController::class, 'adminShow'])->name('reports.show');
        Route::post('reports/{report:uuid}/export', [ReportsController::class, 'export'])->name('reports.export');
    });

    // System Insights (Admin analytics)
    Route::middleware(['can:view-system-insights'])->group(function () {
        Route::get('insights', [InsightsController::class, 'adminIndex'])->name('insights.index');
        Route::get('insights/productivity', [InsightsController::class, 'productivity'])->name('insights.productivity');
        Route::get('insights/departments', [InsightsController::class, 'departments'])->name('insights.departments');
    });

    // User Management
    Route::middleware(['can:view-users'])->group(function () {
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/{user:uuid}', [AdminUserController::class, 'show'])->name('users.show');
    });

    Route::middleware(['can:create-users'])->group(function () {
        Route::get('users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
    });

    Route::middleware(['can:edit-users'])->group(function () {
        Route::get('users/{user:uuid}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user:uuid}', [AdminUserController::class, 'update'])->name('users.update');
    });

    Route::middleware(['can:delete-users'])->group(function () {
        Route::delete('users/{user:uuid}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    });

    // Department Management
    Route::middleware(['can:view-departments'])->group(function () {
        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('departments/{department:uuid}', [DepartmentController::class, 'show'])->name('departments.show');
    });

    Route::middleware(['can:create-departments'])->group(function () {
        Route::get('departments/create', [DepartmentController::class, 'create'])->name('departments.create');
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
    });

    Route::middleware(['can:edit-departments'])->group(function () {
        Route::get('departments/{department:uuid}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::put('departments/{department:uuid}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::patch('departments/{department:uuid}/targets', [DepartmentController::class, 'updateTargets'])->name('departments.updateTargets');
    });

    Route::middleware(['can:delete-departments'])->group(function () {
        Route::delete('departments/{department:uuid}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
    });
});
