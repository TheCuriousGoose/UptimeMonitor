<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleRoleImpersonation;
use App\Http\Middleware\ThrottleMutations;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            // Two ceilings on every web request: a generous global one, and a
            // tighter one that only state-changing verbs consume.
            'throttle:web-global',
            ThrottleMutations::class,
            HandleAppearance::class,
            HandleRoleImpersonation::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            // Sanctum ships these but does not alias them itself.
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // An /api/* route is never a browser page, so every exception it
        // raises must be JSON — including the 401 from a missing token.
        // Without this a client that omits "Accept: application/json" is
        // redirected to the login page and sees HTML instead of an error.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            $headers = $e->getHeaders();
            $retryAfter = (int) ($headers['Retry-After'] ?? 60);

            // Every /api/* request gets JSON regardless of what Accept header
            // the client sent — it is never a browser page, so there is no
            // "best guess" to make the way there is for the web app. A web
            // route hit by a non-Inertia JSON client (a script, a health
            // check) is treated the same way. The Retry-After and
            // X-RateLimit-* headers the limiter set are passed straight
            // through either way.
            if ($request->is('api/*') || ($request->expectsJson() && ! $request->header('X-Inertia'))) {
                return response()->json([
                    'message' => __('base.rate_limited.title'),
                    'retry_after' => $retryAfter,
                ], 429, $headers);
            }

            // An in-app action being throttled ("check now", "send test") reads
            // best as a toast on the page the user is already on. Only for
            // writes: bouncing a throttled GET back where it came from risks a
            // redirect loop, so those fall through to the error screen.
            if ($request->header('X-Inertia') && ! $request->isMethodSafe()) {
                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => __('base.rate_limited.toast', ['seconds' => $retryAfter]),
                ]);

                return back(headers: $headers);
            }

            return Inertia::render('Error', [
                'status' => 429,
                'retryAfter' => $retryAfter,
            ])->toResponse($request)->setStatusCode(429)->withHeaders($headers);
        });
    })->create();
