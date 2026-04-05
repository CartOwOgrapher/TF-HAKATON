<?php

use Illuminate\Support\Facades\Route;
use Modules\Course\Http\Controllers\CourseController;
use Modules\Course\Http\Controllers\CoursePriceController;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('courses')->group(function () {

        Route::post('create', [CourseController::class, 'create']);
        Route::get('list', [CourseController::class, 'list']);
        Route::post('{id}', [CourseController::class, 'update']);
        Route::delete('{id}/soft', [CourseController::class, 'soft']);
        Route::delete('{id}/hard', [CourseController::class, 'hard']);

    });

    Route::prefix('course_price')->group(function () {
        Route::get('{id}/list', [CoursePriceController::class, 'list']);
        Route::post('{id}/create', [CoursePriceController::class, 'create']);

    });

 });