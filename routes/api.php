<?php

use App\Http\Controllers\Api\V1\IncidentController;
use App\Http\Controllers\Api\V1\MonitorCheckController;
use App\Http\Controllers\Api\V1\MonitorController;
use App\Http\Controllers\Api\V1\MonitorStateController;
use App\Http\Controllers\IncidentAcknowledgementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::get('monitors', [MonitorController::class, 'index'])
            ->middleware('abilities:monitors:read')
            ->name('monitors.index');
        Route::post('monitors', [MonitorController::class, 'store'])
            ->middleware('abilities:monitors:write')
            ->name('monitors.store');
        Route::get('monitors/{monitor}', [MonitorController::class, 'show'])
            ->middleware('abilities:monitors:read')
            ->name('monitors.show');
        Route::patch('monitors/{monitor}', [MonitorController::class, 'update'])
            ->middleware('abilities:monitors:write')
            ->name('monitors.update');
        Route::delete('monitors/{monitor}', [MonitorController::class, 'destroy'])
            ->middleware('abilities:monitors:write')
            ->name('monitors.destroy');
        Route::patch('monitors/{monitor}/state', [MonitorStateController::class, 'update'])
            ->middleware('abilities:monitors:write')
            ->name('monitors.state');
        Route::get('monitors/{monitor}/checks', [MonitorController::class, 'checks'])
            ->middleware('abilities:monitors:read')
            ->name('monitors.checks');

        Route::post('monitors/{monitor}/check', [MonitorCheckController::class, 'store'])
            ->middleware(['abilities:checks:trigger', 'throttle:check-now'])
            ->name('monitors.check');

        Route::get('incidents', [IncidentController::class, 'index'])
            ->middleware('abilities:incidents:read')
            ->name('incidents.index');
        Route::get('incidents/{incident}', [IncidentController::class, 'show'])
            ->middleware('abilities:incidents:read')
            ->name('incidents.show');
        Route::post('incidents/{incident}/acknowledge', [IncidentAcknowledgementController::class, 'store'])
            ->middleware('abilities:incidents:write')
            ->name('incidents.acknowledge');
    });
