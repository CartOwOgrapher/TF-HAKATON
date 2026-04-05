<?php

use Illuminate\Support\Facades\Route;
use Modules\Training\Http\Controllers\TrainingGroupController;
use Modules\Training\Http\Controllers\GroupParticipantController;
use Modules\Training\Http\Controllers\CertificateController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('training-groups', [TrainingGroupController::class, 'index']);
    Route::post('training-groups', [TrainingGroupController::class, 'store']);
    Route::get('training-groups/{training_group}', [TrainingGroupController::class, 'show']);
    Route::put('training-groups/{training_group}', [TrainingGroupController::class, 'update']);
    Route::delete('training-groups/{training_group}', [TrainingGroupController::class, 'destroy']);
    Route::patch('training-groups/{training_group}/status', [TrainingGroupController::class, 'changeStatus']);
    Route::get('training-groups/{training_group}/conflicts', [TrainingGroupController::class, 'conflicts']);


    Route::get('training-groups/{training_group}/participants', [GroupParticipantController::class, 'index']);
    Route::post('training-groups/{training_group}/participants', [GroupParticipantController::class, 'store']);
    Route::patch('training-groups/{training_group}/participants/{participant}', [GroupParticipantController::class, 'update']);
    Route::delete('training-groups/{training_group}/participants/{participant}', [GroupParticipantController::class, 'destroy']);


    Route::post('training-groups/{training_group}/participants/{participant}/certificate', [CertificateController::class, 'upload']);
    Route::get('training-groups/{training_group}/participants/{participant}/certificate', [CertificateController::class, 'download']);
    Route::delete('training-groups/{training_group}/participants/{participant}/certificate', [CertificateController::class, 'destroy']);
});
