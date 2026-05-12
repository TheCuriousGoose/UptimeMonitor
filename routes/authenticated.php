<?php

use App\Http\Controllers\MonitorController;
use Illuminate\Support\Facades\Route;

Route::inertia('dashboard', 'Dashboard')->name('dashboard');

Route::resource('monitors', MonitorController::class);

include __DIR__ . '/settings.php';