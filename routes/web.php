<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('/search', SearchController::class)->name('search');
});

require __DIR__.'/crm.php';
require __DIR__.'/settings.php';
