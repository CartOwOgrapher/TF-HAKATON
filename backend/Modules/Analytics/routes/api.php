<?php

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Http\Controllers\AnalyticsController;

Route::middleware('auth:sanctum')->prefix('analytics')->group(function () {

    Route::get('companies', [AnalyticsController::class, 'companies']);

    Route::get('companies/{id}', [AnalyticsController::class, 'companyDetail']);
});
