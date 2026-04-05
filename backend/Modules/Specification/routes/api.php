<?php

use Illuminate\Support\Facades\Route;
use Modules\Specification\Http\Controllers\SpecificationController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('specifications', [SpecificationController::class, 'index']);
    Route::post('specifications', [SpecificationController::class, 'store']);
    Route::get('specifications/{specification}', [SpecificationController::class, 'show']);
    Route::put('specifications/{specification}', [SpecificationController::class, 'update']);
    Route::delete('specifications/{specification}', [SpecificationController::class, 'destroy']);

    Route::post('specifications/{specification}/groups/{training_group}', [SpecificationController::class, 'attachGroup']);
    Route::delete('specifications/{specification}/groups/{training_group}', [SpecificationController::class, 'detachGroup']);
});
