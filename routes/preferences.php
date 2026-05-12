<?php

use App\Http\Controllers\PreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/me/preferences', [PreferenceController::class, 'index']);
    Route::patch('/me/preferences', [PreferenceController::class, 'update']);
});
