<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\MaintenanceWindowController;
use App\Http\Controllers\MonitorBulkController;
use App\Http\Controllers\MonitorCheckController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\MonitorSearchController;
use App\Http\Controllers\MonitorStateController;
use App\Http\Controllers\NotificationChannelTestController;
use App\Http\Controllers\StatusPageController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Declared before the resource so these are not swallowed by {monitor}.
Route::post('monitors/bulk', [MonitorBulkController::class, 'store'])->name('monitors.bulk');
Route::get('monitors/search', [MonitorSearchController::class, 'index'])->name('monitors.search');

Route::resource('monitors', MonitorController::class);
Route::patch('monitors/{monitor}/state', [MonitorStateController::class, 'update'])->name('monitors.state');

// Makes the server issue an outbound request to a user-supplied target, so it
// is throttled far harder than an ordinary write.
Route::post('monitors/{monitor}/check', [MonitorCheckController::class, 'store'])
    ->middleware('throttle:check-now')
    ->name('monitors.check');

Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');

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
