<?php

use App\Http\Controllers\Authentication\DevLoginController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\PublicStatusPageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'marketing/Home', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::inertia('features', 'marketing/Features')->name('features');
Route::inertia('about', 'marketing/About')->name('about');
Route::inertia('contact', 'marketing/Contact')->name('contact');
Route::inertia('roadmap', 'marketing/Roadmap')->name('roadmap');

Route::get('privacy', [ContentController::class, 'legal'])
    ->defaults('slug', 'privacy')
    ->name('privacy');
Route::get('terms', [ContentController::class, 'legal'])
    ->defaults('slug', 'terms')
    ->name('terms');

Route::get('docs', [ContentController::class, 'docs'])->name('docs.index');
Route::get('blog', [ContentController::class, 'blog'])->name('blog.index');
Route::get('changelog', [ContentController::class, 'changelog'])->name('changelog.index');
Route::get('{segment}/{slug}', [ContentController::class, 'show'])
    ->whereIn('segment', ['docs', 'blog', 'changelog'])
    ->name('content.show');

// Public status pages are intentionally outside the auth group.
Route::get('status/{slug}', [PublicStatusPageController::class, 'show'])->name('status.show');

Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::middleware(['auth', 'verified'])->group(function () {
    require __DIR__.'/authenticated.php';
    require __DIR__.'/settings.php';
});

require __DIR__.'/preferences.php';
require __DIR__.'/oauth.php';

// Dev login routes (local only)
if (app()->isLocal()) {
    Route::post('/dev-login/admin', [DevLoginController::class, 'loginAsAdmin'])->name('dev.login.admin');
    Route::post('/dev-login/user', [DevLoginController::class, 'loginAsUser'])->name('dev.login.user');
}
