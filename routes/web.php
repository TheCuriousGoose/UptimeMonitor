<?php

use App\Http\Controllers\Authentication\DevLoginController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\PublicStatusPageController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'marketing/Home', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

// Marketing pages — static copy, no controller state of their own.
Route::inertia('features', 'marketing/Features')->name('features');
Route::inertia('pricing', 'marketing/Pricing')->name('pricing');
Route::inertia('about', 'marketing/About')->name('about');
Route::inertia('contact', 'marketing/Contact')->name('contact');
Route::inertia('roadmap', 'marketing/Roadmap')->name('roadmap');
Route::inertia('privacy', 'marketing/Privacy')->name('privacy');
Route::inertia('terms', 'marketing/Terms')->name('terms');

// Public content readers, backed by content_entries.
Route::get('docs', [ContentController::class, 'docs'])->name('docs.index');
Route::get('blog', [ContentController::class, 'blog'])->name('blog.index');
Route::get('changelog', [ContentController::class, 'changelog'])->name('changelog.index');
Route::get('{segment}/{slug}', [ContentController::class, 'show'])
    ->whereIn('segment', ['docs', 'blog', 'changelog'])
    ->name('content.show');

// Public status pages are intentionally outside the auth group.
Route::get('status/{slug}', [PublicStatusPageController::class, 'show'])->name('status.show');

Route::middleware(['auth', 'verified'])->group(function () {
    require __DIR__.'/authenticated.php';
    // Was previously required outside this group — profile, security,
    // appearance, password, and API-key management were reachable by guests.
    require __DIR__.'/settings.php';
});

require __DIR__.'/preferences.php';
require __DIR__.'/oauth.php';

// Dev login routes (local only)
if (app()->isLocal()) {
    Route::post('/dev-login/admin', [DevLoginController::class, 'loginAsAdmin'])->name('dev.login.admin');
    Route::post('/dev-login/user', [DevLoginController::class, 'loginAsUser'])->name('dev.login.user');
}
