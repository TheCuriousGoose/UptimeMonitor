<?php

use App\Http\Controllers\Settings\ApiTokenController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\VerifiedDomainController;
use Illuminate\Support\Facades\Route;

Route::redirect('settings', '/settings/profile');

Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

Route::put('settings/password', [SecurityController::class, 'update'])
    ->middleware('throttle:6,1')
    ->name('user-password.update');

Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

Route::get('settings/domains', [VerifiedDomainController::class, 'index'])->name('domains.index');
Route::post('settings/domains', [VerifiedDomainController::class, 'store'])->name('domains.store');
Route::delete('settings/domains/{domain}', [VerifiedDomainController::class, 'destroy'])->name('domains.destroy');

// Each attempt makes the server resolve DNS and fetch a URL for a host the
// user named, so it shares the outbound budget rather than the mutation one.
Route::post('settings/domains/{domain}/verify', [VerifiedDomainController::class, 'verify'])
    ->middleware('throttle:check-now')
    ->name('domains.verify');

Route::get('settings/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
Route::post('settings/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
Route::delete('settings/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
