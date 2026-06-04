<?php

use App\Http\Controllers\Api\V1\BulkJobApiController;
use App\Http\Controllers\Api\V1\EmailCheckController;
use App\Http\Middleware\AuthenticateApiKey;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(AuthenticateApiKey::class)->group(function () {
    // Single email check
    Route::post('/email/check', [EmailCheckController::class, 'check']);

    // Bulk jobs
    Route::get('/bulk-jobs', [BulkJobApiController::class, 'index']);
    Route::post('/bulk-jobs', [BulkJobApiController::class, 'store']);
    Route::get('/bulk-jobs/{id}', [BulkJobApiController::class, 'show']);
    Route::get('/bulk-jobs/{id}/results', [BulkJobApiController::class, 'results']);
    Route::get('/bulk-jobs/{id}/export', [BulkJobApiController::class, 'export']);
});
