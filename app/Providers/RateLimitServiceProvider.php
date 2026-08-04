<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Every rate limit in the application, in one place.
 *
 * Limits are keyed by user id where we have one so that a shared egress IP
 * (an office, a VPN, a school) cannot let one tenant exhaust everyone else's
 * budget. Guests fall back to IP, which is all we have.
 *
 * Fortify's login and two-factor limiters live in FortifyServiceProvider,
 * because Fortify resolves them by name from its own config.
 */
class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Requests a signed-in user may make per minute before we start refusing.
     * Generous: an Inertia SPA fires several XHRs per interaction.
     */
    private const GLOBAL_PER_MINUTE = 300;

    /** Writes are cheaper to abuse than reads, so they get their own ceiling. */
    private const MUTATION_PER_MINUTE = 60;

    /**
     * "Check now" makes the server issue an outbound HTTP/DNS/TCP request to a
     * user-supplied target. Left open it is a traffic amplifier pointed at
     * third parties, so it is the tightest limit in the app.
     */
    private const CHECK_NOW_PER_MINUTE = 6;

    /**
     * A channel test sends a real email/Slack/Discord/webhook message. Abuse
     * here costs money and burns sender reputation.
     */
    private const CHANNEL_TEST_PER_MINUTE = 3;

    /**
     * The general API ceiling. Generous enough for a script or integration
     * doing routine polling, bounded enough that one leaked key cannot take
     * the database down.
     */
    private const API_PER_MINUTE = 120;

    public function boot(): void
    {
        RateLimiter::for('web-global', fn (Request $request) => Limit::perMinute(self::GLOBAL_PER_MINUTE)->by($this->key($request)));

        RateLimiter::for('mutations', fn (Request $request) => Limit::perMinute(self::MUTATION_PER_MINUTE)->by($this->key($request)));

        // check-now is shared by the web action and its API twin, keyed by
        // user rather than token: minting a second key must not raise the
        // ceiling on how many outbound checks one account can force.
        RateLimiter::for('check-now', fn (Request $request) => Limit::perMinute(self::CHECK_NOW_PER_MINUTE)->by($this->key($request)));

        RateLimiter::for('channel-test', fn (Request $request) => Limit::perMinute(self::CHANNEL_TEST_PER_MINUTE)->by($this->key($request)));

        // Keyed by token, not user: each key an account issues gets its own
        // budget, so one busy integration cannot starve another.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(self::API_PER_MINUTE)->by($this->apiKey($request));
        });
    }

    /**
     * Prefer the user id so tenants behind one NAT get independent budgets.
     */
    private function key(Request $request): string
    {
        return $request->user()?->getAuthIdentifier()
            ? 'user:'.$request->user()->getAuthIdentifier()
            : 'ip:'.$request->ip();
    }

    private function apiKey(Request $request): string
    {
        $token = $request->user()?->currentAccessToken();

        return $token ? 'token:'.$token->getKey() : $this->key($request);
    }
}
