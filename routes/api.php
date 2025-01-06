<?php

use App\Http\Controllers\ReportsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
  Route::get(
    '/reports/dashboard',
    [ReportsController::class, 'dashboard']
  );
});
