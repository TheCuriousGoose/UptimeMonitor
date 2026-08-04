<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonitorCheckController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\MonitorStateController;
use App\Http\Controllers\NotificationChannelController;
use App\Http\Controllers\NotificationChannelTestController;
use App\Http\Controllers\StatusPageController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('monitors', MonitorController::class);
Route::patch('monitors/{monitor}/state', [MonitorStateController::class, 'update'])->name('monitors.state');
Route::post('monitors/{monitor}/check', [MonitorCheckController::class, 'store'])->name('monitors.check');

Route::resource('channels', NotificationChannelController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->parameters(['channels' => 'channel']);
Route::post('channels/{channel}/test', [NotificationChannelTestController::class, 'store'])->name('channels.test');

Route::resource('status-pages', StatusPageController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->parameters(['status-pages' => 'status_page']);

include __DIR__.'/settings.php';
include __DIR__.'/admin.php';
