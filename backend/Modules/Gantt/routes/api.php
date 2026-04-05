<?php

use Illuminate\Support\Facades\Route;
use Modules\Gantt\Http\Controllers\GanttController;

Route::middleware('auth:sanctum')->prefix('gantt')->name('gantt.')->group(function () {

    Route::get('/', [GanttController::class, 'index'])->name('index');

    Route::get('/export', [GanttController::class, 'export'])->name('export');

    Route::patch('/{trainingGroup}/dates', [GanttController::class, 'updateDates'])->name('dates');

    Route::patch('/{trainingGroup}/color', [GanttController::class, 'updateColor'])->name('color');
});