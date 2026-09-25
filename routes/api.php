<?php

use App\Http\Controllers\API\AlertsController;
use App\Http\Controllers\API\GeoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/alerts/county/{ugc}', [AlertsController::class, 'byCounty']);
    Route::get('/alerts/points', [AlertsController::class, 'byPoints']);
    Route::get('/geo/points', [GeoController::class, 'resolveMetadataFromPoint']);
    Route::get('/geo/ugc', [GeoController::class, 'resolveUgcFromPoint']);
});
