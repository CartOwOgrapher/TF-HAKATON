<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\EmployeeController;

Route::middleware('auth:sanctum')->prefix('employees')->group(function () {

    Route::post('create', [EmployeeController::class, 'create']);
    Route::get('list', [EmployeeController::class, 'list']);
    Route::post('{id}', [EmployeeController::class, 'update']);
    Route::delete('{id}/soft', [EmployeeController::class, 'soft']);
    Route::delete('{id}/hard', [EmployeeController::class, 'hard']);
});