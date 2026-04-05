<?php

use Illuminate\Support\Facades\Route;
use Modules\Xml\Http\Controllers\XmlController;
use Modules\Xml\Http\Controllers\XmlImportController;

Route::middleware('auth:sanctum')->prefix('xml')->name('xml.')->group(function () {
 
    Route::post('/import', [XmlImportController::class, 'import'])->name('import');
 
    Route::get('/batches', [XmlImportController::class, 'batches'])->name('batches.index');
    Route::get('/batches/{batch}', [XmlImportController::class, 'batchShow'])->name('batches.show');
 
    Route::get('/batches/{batch}/logs', [XmlImportController::class, 'batchLogs'])->name('batches.logs');
});
 