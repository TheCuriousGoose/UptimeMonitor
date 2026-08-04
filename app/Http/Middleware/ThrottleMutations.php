<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * Applies the "mutations" limiter to state-changing requests only.
 *
 * Reads are already covered by the global web limiter and are far cheaper, so
 * throttling them at the same ceiling would punish ordinary SPA navigation.
 */
class ThrottleMutations extends ThrottleRequests
{
    public function handle($request, Closure $next, $maxAttempts = 'mutations', $decayMinutes = 1, $prefix = '')
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        // Must be exactly three arguments: the parent only resolves a *named*
        // limiter when func_num_args() === 3.
        return parent::handle($request, $next, $maxAttempts);
    }
}
