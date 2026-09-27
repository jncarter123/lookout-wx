<?php

use App\Http\Controllers\API\AlertsController;
use App\Http\Controllers\API\GeoController;
use App\Http\Controllers\API\NwsCompatibleController;
use Illuminate\Support\Facades\Route;

/*
| NWS-compatible alert endpoints: the NWS API's /alerts/active and /alerts/{id}, in its
| own response shape. Public, as api.weather.gov is, and limited per client IP instead.
| A client switches from NWS by setting its base URL to https://{this host}/api/nws.
| `active` must stay above `{id}`, which would otherwise swallow it.
*/
Route::prefix('nws')->middleware('throttle:nws-compatible')->group(function () {
    Route::get('/alerts/active', [NwsCompatibleController::class, 'active'])->name('nws.alerts.active');
    Route::get('/alerts/{id}', [NwsCompatibleController::class, 'show'])->name('nws.alerts.show');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/alerts/changes', [AlertsController::class, 'changes']);
    Route::get('/alerts/county/{ugc}', [AlertsController::class, 'byCounty']);
    Route::get('/alerts/points', [AlertsController::class, 'byPoints']);
    Route::get('/geo/points', [GeoController::class, 'resolveMetadataFromPoint']);
    Route::get('/geo/ugc', [GeoController::class, 'resolveUgcFromPoint']);
});
