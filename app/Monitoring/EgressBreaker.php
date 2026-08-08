<?php

namespace App\Monitoring;

use Illuminate\Support\Facades\RateLimiter;

/**
 * The last thing between a scheduled check and a third party's server.
 *
 * Validation bounds what one account can configure; this bounds what the
 * instance actually emits, whoever configured it. It is the only control that
 * survives an attacker spreading monitors across many accounts, because it
 * counts requests per domain rather than per user.
 */
class EgressBreaker
{
    private const WINDOW = 60;

    public function limit(): ?int
    {
        $limit = config('monitoring.abuse.max_requests_per_minute_per_domain');

        return $limit === null ? null : (int) $limit;
    }

    /**
     * Consume one request against the domain's budget, or report that the
     * budget is spent.
     */
    public function attempt(TargetIdentity $identity): bool
    {
        $limit = $this->limit();

        if ($limit === null) {
            return true;
        }

        $key = $this->key($identity);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return false;
        }

        RateLimiter::hit($key, self::WINDOW);

        return true;
    }

    public function availableIn(TargetIdentity $identity): int
    {
        return RateLimiter::availableIn($this->key($identity));
    }

    private function key(TargetIdentity $identity): string
    {
        return 'egress:'.$identity->domain;
    }
}
