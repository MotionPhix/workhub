<?php

use App\Http\Controllers\Manager\ManagerDashboardController;
use App\Http\Controllers\Manager\TeamController;
use App\Http\Controllers\Manager\TeamInsightsController;
use App\Http\Controllers\Manager\TeamReportsController;
use App\Http\Controllers\Manager\TeamWorkEntriesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Manager Routes
|--------------------------------------------------------------------------
|
| These routes are for manager users who need team oversight capabilities
| but don't have full admin access. Managers can view and manage their
| team members' work, generate team reports, and access team insights.
|
*/

Route::middleware(['auth', 'role.access'])->prefix('manager')->name('manager.')->group(function () {

    // Manager Dashboard
    Route::get('/', [ManagerDashboardController::class, 'index'])->name('dashboard');

    // Report approval actions
    Route::middleware(['can:approve-reports'])->group(function () {
        Route::post('/reports/{report}/approve', [ManagerDashboardController::class, 'approveReport'])->name('reports.approve');
        Route::post('/reports/{report}/reject', [ManagerDashboardController::class, 'rejectReport'])->name('reports.reject');
        Route::post('/reports/bulk-approve', [ManagerDashboardController::class, 'bulkApproveReports'])->name('reports.bulk-approve');
    });

    // Analytics export
    Route::middleware(['can:export-analytics'])->group(function () {
        Route::get('/analytics/export', [ManagerDashboardController::class, 'exportAnalytics'])->name('analytics.export');
    });

    // Team Management
    Route::middleware(['can:view-team-work-entries'])->prefix('team')->name('team.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');

        // Team Performance
        Route::get('/performance', [ManagerDashboardController::class, 'teamPerformance'])->name('performance');

        // Team member invitations (must come before parameterized routes)
        Route::middleware(['can:create-invitations'])->group(function () {
            Route::get('/invitations', [TeamController::class, 'invitations'])->name('invitations');
            Route::get('/invite', [TeamController::class, 'createInvite'])->name('invite.create');
            Route::post('/invite', [TeamController::class, 'invite'])->name('invite');
            Route::post('/invitations/{invitation}/resend', [TeamController::class, 'resendInvitation'])->name('invitations.resend');
            Route::delete('/invitations/{invitation}/cancel', [TeamController::class, 'cancelInvitation'])->name('invitations.cancel');
        });

        // Team Work Entries Management (moved from separate prefix)
        Route::middleware(['can:view-team-work-entries'])->prefix('work-entries')->name('work-entries.')->group(function () {
            Route::get('/', [TeamWorkEntriesController::class, 'index'])->name('index');
            Route::get('/team-summary', [TeamWorkEntriesController::class, 'teamSummary'])->name('team-summary');
            Route::get('/{entry:uuid}', [TeamWorkEntriesController::class, 'show'])->name('show');

            // Work entry approval/management
            Route::middleware(['can:approve-team-work-entries'])->group(function () {
                Route::post('/{entry:uuid}/approve', [TeamWorkEntriesController::class, 'approve'])->name('approve');
                Route::post('/{entry:uuid}/reject', [TeamWorkEntriesController::class, 'reject'])->name('reject');
                Route::post('/{entry:uuid}/request-changes', [TeamWorkEntriesController::class, 'requestChanges'])->name('request-changes');
            });
        });

        // Team member management
        Route::middleware(['can:manage-team-members'])->group(function () {
            Route::get('/export', [TeamController::class, 'export'])->name('export');
        });

        // Parameterized routes (must come after specific routes)
        Route::get('/{user:uuid}', [TeamController::class, 'show'])->name('show');
        Route::get('/{user:uuid}/profile', [TeamController::class, 'profile'])->name('profile');
        Route::middleware(['can:manage-team-members'])->group(function () {
            Route::put('/{user:uuid}', [TeamController::class, 'update'])->name('update');
        });
    });


    // Team Reports
    Route::middleware(['can:view-team-reports'])->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [TeamReportsController::class, 'index'])->name('index');
        Route::get('/{report:uuid}', [TeamReportsController::class, 'show'])->name('show');
        Route::get('/productivity', [TeamReportsController::class, 'productivity'])->name('productivity');
        Route::get('/attendance', [TeamReportsController::class, 'attendance'])->name('attendance');
        Route::get('/performance', [TeamReportsController::class, 'performance'])->name('performance');
        Route::get('/custom', [TeamReportsController::class, 'custom'])->name('custom');

        // Report generation and export
        Route::middleware(['can:generate-team-reports'])->group(function () {
            Route::post('/generate', [TeamReportsController::class, 'generate'])->name('generate');
            Route::post('/{report:uuid}/export', [TeamReportsController::class, 'export'])->name('export');
        });
    });

    // Team Insights & Analytics
    Route::middleware(['can:view-team-insights'])->prefix('insights')->name('insights.')->group(function () {
        Route::get('/', [TeamInsightsController::class, 'index'])->name('index');
        Route::get('/productivity-trends', [TeamInsightsController::class, 'productivityTrends'])->name('productivity-trends');
        Route::get('/team-performance', [TeamInsightsController::class, 'teamPerformance'])->name('team-performance');
        Route::get('/workload-distribution', [TeamInsightsController::class, 'workloadDistribution'])->name('workload-distribution');
        Route::get('/individual-metrics/{user:uuid}', [TeamInsightsController::class, 'individualMetrics'])->name('individual-metrics');
    });

    // Team Member Management (limited user management for team members)
    Route::middleware(['can:edit-team-work-entries'])->prefix('team-members')->name('team-members.')->group(function () {
        Route::get('/{user:uuid}/work-history', [TeamController::class, 'workHistory'])->name('work-history');
        Route::get('/{user:uuid}/performance', [TeamController::class, 'performance'])->name('performance');
        Route::post('/{user:uuid}/feedback', [TeamController::class, 'provideFeedback'])->name('feedback');
        Route::get('/{user:uuid}/goals', [TeamController::class, 'goals'])->name('goals');
        Route::post('/{user:uuid}/goals', [TeamController::class, 'setGoals'])->name('goals.store');
    });

    // Manager Profile & Settings
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ManagerDashboardController::class, 'profile'])->name('index');
        Route::get('/settings', [ManagerDashboardController::class, 'settings'])->name('settings');
        Route::put('/settings', [ManagerDashboardController::class, 'updateSettings'])->name('settings.update');
    });

    // Quick Actions API endpoints for manager dashboard
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/team-stats', [ManagerDashboardController::class, 'getTeamStats'])->name('team-stats');
        Route::get('/pending-approvals', [TeamWorkEntriesController::class, 'getPendingApprovals'])->name('pending-approvals');
        Route::get('/team-activity', [TeamController::class, 'getTeamActivity'])->name('team-activity');
    });
});
