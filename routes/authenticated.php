<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentAcknowledgementController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IncidentUpdateController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\MaintenanceWindowController;
use App\Http\Controllers\MonitorBulkController;
use App\Http\Controllers\MonitorCheckController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\MonitorPreviewController;
use App\Http\Controllers\MonitorSearchController;
use App\Http\Controllers\MonitorStateController;
use App\Http\Controllers\NotificationChannelTestController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\StatusPageController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('get-started', [OnboardingController::class, 'show'])->name('onboarding.show');
Route::post('get-started', [OnboardingController::class, 'store'])->name('onboarding.store');
Route::post('get-started/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');

Route::post('monitors/bulk', [MonitorBulkController::class, 'store'])->name('monitors.bulk');
Route::get('monitors/search', [MonitorSearchController::class, 'index'])->name('monitors.search');

Route::post('monitors/preview', [MonitorPreviewController::class, 'store'])
    ->middleware('throttle:check-now')
    ->name('monitors.preview');

Route::resource('monitors', MonitorController::class);
Route::patch('monitors/{monitor}/state', [MonitorStateController::class, 'update'])->name('monitors.state');

Route::post('monitors/{monitor}/check', [MonitorCheckController::class, 'store'])
    ->middleware('throttle:check-now')
    ->name('monitors.check');

Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
Route::get('incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');

Route::post('incidents/{incident}/acknowledge', [IncidentAcknowledgementController::class, 'store'])
    ->name('incidents.acknowledge.store');
Route::delete('incidents/{incident}/acknowledge', [IncidentAcknowledgementController::class, 'destroy'])
    ->name('incidents.acknowledge.destroy');

Route::post('incidents/{incident}/updates', [IncidentUpdateController::class, 'store'])
    ->name('incidents.updates.store');
Route::patch('incident-updates/{incident_update}', [IncidentUpdateController::class, 'update'])
    ->name('incident-updates.update');
Route::delete('incident-updates/{incident_update}', [IncidentUpdateController::class, 'destroy'])
    ->name('incident-updates.destroy');

Route::resource('integrations', IntegrationController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->parameters(['integrations' => 'integration']);

// Sends a real notification — abuse costs money and sender reputation. The
// parameter is named {channel} to match the controller's type-hinted binding.
Route::post('integrations/{channel}/test', [NotificationChannelTestController::class, 'store'])
    ->middleware('throttle:channel-test')
    ->name('integrations.test');

Route::resource('maintenance-windows', MaintenanceWindowController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->parameters(['maintenance-windows' => 'maintenance_window']);

Route::resource('status-pages', StatusPageController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->parameters(['status-pages' => 'status_page']);

include __DIR__.'/settings.php';
include __DIR__.'/admin.php';
