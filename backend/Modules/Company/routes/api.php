<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\Http\Controllers\CompanyController;

Route::middleware('auth:sanctum')->prefix('companies')->group(function () {

    Route::post('create', [CompanyController::class, 'create']);
    Route::get('list', [CompanyController::class, 'list']);
    Route::post('{id}', [CompanyController::class, 'update']);
    Route::delete('{id}/soft', [CompanyController::class, 'soft']);
    Route::delete('{id}/hard', [CompanyController::class, 'hard']);
});