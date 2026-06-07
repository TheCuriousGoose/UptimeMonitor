<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    require __DIR__.'/authenticated.php';
});

require __DIR__.'/settings.php';
require __DIR__.'/preferences.php';
require __DIR__.'/oauth.php';

// Dev login routes (local only)
if (app()->isLocal()) {
    Route::post('/dev-login/admin', [\App\Http\Controllers\Authentication\DevLoginController::class, 'loginAsAdmin'])->name('dev.login.admin');
    Route::post('/dev-login/user', [\App\Http\Controllers\Authentication\DevLoginController::class, 'loginAsUser'])->name('dev.login.user');
}