<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleRoleImpersonation;
use App\Http\Middleware\HandleSeo;
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
use Symfony\Component\HttpFoundation\Response;

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
            HandleSeo::class,
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

        // Everything else that a browser can actually land on. Anything not
        // listed keeps Laravel's own response — a redirect, or a bare status
        // for the codes no user ever reads.
        //
        // 503 is deliberately absent. Maintenance mode is exactly the moment
        // Inertia cannot be relied on: the request is refused before the app
        // is fully up, and built assets may be mid-swap. It is served by the
        // self-contained resources/views/errors/503.blade.php instead.
        $errorPageStatuses = [403, 404, 419, 500];

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) use ($errorPageStatuses) {
            // JSON clients already got a JSON body from shouldRenderJsonWhen()
            // above; re-rendering it as a page would break them.
            if ($request->is('api/*') || ($request->expectsJson() && ! $request->header('X-Inertia'))) {
                return $response;
            }

            // 429 is handled by the render() callback above, which turns an
            // in-app write into a toast rather than a full-page error. render()
            // callbacks run first, so without this guard that work is undone.
            if ($response->getStatusCode() === 429) {
                return $response;
            }

            if (! in_array($response->getStatusCode(), $errorPageStatuses, true)) {
                return $response;
            }

            // With debug on, the stack trace is worth more than a branded page.
            if (config('app.debug') && $response->getStatusCode() === 500) {
                return $response;
            }

            // A 404 for an unmatched URI is thrown before the "web" group runs,
            // so HandleInertiaRequests never shares auth/settings/flash. The
            // page has to treat every shared prop as optional.
            return Inertia::render('Error', ['status' => $response->getStatusCode()])
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
        });
    })->create();
