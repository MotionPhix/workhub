<?php

use App\Http\Controllers\Crm\AnalyticsController;
use App\Http\Controllers\Crm\ClientController;
use App\Http\Controllers\Crm\CompanyController;
use App\Http\Controllers\Crm\DashboardController;
use App\Http\Controllers\Crm\FollowUpController;
use App\Http\Controllers\Crm\OutreachController;
use App\Http\Controllers\Crm\OutreachCycleController;
use App\Http\Controllers\Crm\PipelineController;
use App\Http\Controllers\Crm\ProposalController;
use App\Http\Controllers\Crm\ProspectController;
use App\Http\Controllers\Crm\SearchController;
use App\Http\Controllers\Crm\TemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('search', [SearchController::class, 'index'])->name('search');

    Route::prefix('prospects')->as('prospects.')->group(function (): void {
        Route::get('/', [ProspectController::class, 'index'])->name('index');
        Route::get('create', [ProspectController::class, 'create'])->name('create');
        Route::post('/', [ProspectController::class, 'store'])->name('store');
        Route::get('{prospect}', [ProspectController::class, 'show'])->name('show');
        Route::get('{prospect}/edit', [ProspectController::class, 'edit'])->name('edit');
        Route::put('{prospect}', [ProspectController::class, 'update'])->name('update');
        Route::delete('{prospect}', [ProspectController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('companies')->as('companies.')->group(function (): void {
        Route::get('/', [CompanyController::class, 'index'])->name('index');
        Route::get('create', [CompanyController::class, 'create'])->name('create');
        Route::post('/', [CompanyController::class, 'store'])->name('store');
        Route::get('{company}', [CompanyController::class, 'show'])->name('show');
        Route::get('{company}/edit', [CompanyController::class, 'edit'])->name('edit');
        Route::put('{company}', [CompanyController::class, 'update'])->name('update');
        Route::delete('{company}', [CompanyController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('outreach')->as('outreach.')->group(function (): void {
        Route::get('/', [OutreachController::class, 'index'])->name('index');
        Route::post('assignments/{assignment}/contact', [OutreachController::class, 'markContacted'])->name('mark-contacted');
        Route::post('assignments/{assignment}/skip', [OutreachController::class, 'skip'])->name('skip');
        Route::post('assignments/{assignment}/snooze', [OutreachController::class, 'snooze'])->name('snooze');
        Route::post('assignments/{assignment}/draft', [OutreachController::class, 'draftEmail'])->name('draft-email');

        Route::prefix('cycles')->as('cycles.')->group(function (): void {
            Route::get('/', [OutreachCycleController::class, 'index'])->name('index');
            Route::get('{cycle}', [OutreachCycleController::class, 'show'])->name('show');
        });
    });

    Route::prefix('follow-ups')->as('follow-ups.')->group(function (): void {
        Route::get('/', [FollowUpController::class, 'index'])->name('index');
        Route::put('{followUp}', [FollowUpController::class, 'update'])->name('update');
        Route::delete('{followUp}', [FollowUpController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('proposals')->as('proposals.')->group(function (): void {
        Route::get('/', [ProposalController::class, 'index'])->name('index');
        Route::get('create', [ProposalController::class, 'create'])->name('create');
        Route::post('/', [ProposalController::class, 'store'])->name('store');
        Route::get('{proposal}', [ProposalController::class, 'show'])->name('show');
        Route::get('{proposal}/edit', [ProposalController::class, 'edit'])->name('edit');
        Route::put('{proposal}', [ProposalController::class, 'update'])->name('update');
        Route::post('{proposal}/export', [ProposalController::class, 'exportPdf'])->name('export-pdf');
    });

    Route::prefix('pipeline')->as('pipeline.')->group(function (): void {
        Route::get('/', [PipelineController::class, 'index'])->name('index');
        Route::post('opportunities', [PipelineController::class, 'store'])->name('store');
        Route::put('opportunities/{opportunity}/stage', [PipelineController::class, 'updateStage'])->name('update-stage');
        Route::put('opportunities/{opportunity}', [PipelineController::class, 'update'])->name('update');
    });

    Route::prefix('clients')->as('clients.')->group(function (): void {
        Route::get('/', [ClientController::class, 'index'])->name('index');
        Route::get('{client}', [ClientController::class, 'show'])->name('show');
        Route::get('{client}/edit', [ClientController::class, 'edit'])->name('edit');
        Route::put('{client}', [ClientController::class, 'update'])->name('update');
        Route::delete('{client}', [ClientController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('templates')->as('templates.')->group(function (): void {
        Route::get('/', [TemplateController::class, 'index'])->name('index');
        Route::get('email/{template}/edit', [TemplateController::class, 'editEmail'])->name('edit-email');
        Route::put('email/{template}', [TemplateController::class, 'updateEmail'])->name('update-email');
        Route::get('proposal/{template}/edit', [TemplateController::class, 'editProposal'])->name('edit-proposal');
        Route::put('proposal/{template}', [TemplateController::class, 'updateProposal'])->name('update-proposal');
    });

    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics');
});
