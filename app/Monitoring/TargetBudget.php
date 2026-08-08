<?php

namespace App\Monitoring;

use App\Models\Monitor;
use App\Models\User;
use App\Models\VerifiedDomain;

/**
 * How much traffic the instance is allowed to aim at one domain.
 *
 * Budgets are expressed as requests per minute rather than monitor counts, so
 * three monitors at 30s and thirty at 5m are compared on the thing that
 * actually reaches the target.
 */
class TargetBudget
{
    /**
     * Requests per minute already committed against a domain by active
     * monitors, optionally scoped to one user and excluding one monitor.
     */
    public function committedRate(string $domain, ?User $user = null, ?int $excludingMonitorId = null): float
    {
        $query = Monitor::query()
            ->forDomain($domain)
            ->where('is_active', true)
            ->when($user !== null, fn ($q) => $q->where('created_by', $user->id))
            ->when($excludingMonitorId !== null, fn ($q) => $q->whereKeyNot($excludingMonitorId));

        return (float) $query->get(['interval_seconds'])
            ->sum(fn (Monitor $monitor) => $monitor->requestsPerMinute());
    }

    /**
     * The first limit a proposed monitor would breach, or null if it fits.
     */
    public function exceeded(
        TargetIdentity $identity,
        int $intervalSeconds,
        User $user,
        ?int $excludingMonitorId = null,
    ): ?BudgetBreach {
        $proposed = 60 / max(1, $intervalSeconds);

        $perUser = config('monitoring.abuse.max_requests_per_minute_per_domain_per_user');

        if ($perUser !== null) {
            $rate = $this->committedRate($identity->domain, $user, $excludingMonitorId) + $proposed;

            if ($rate > $perUser) {
                return new BudgetBreach($identity->domain, $rate, (int) $perUser, perUser: true);
            }
        }

        $instance = config('monitoring.abuse.max_requests_per_minute_per_domain');

        if ($instance !== null) {
            $rate = $this->committedRate($identity->domain, null, $excludingMonitorId) + $proposed;

            if ($rate > $instance) {
                return new BudgetBreach($identity->domain, $rate, (int) $instance, perUser: false);
            }
        }

        return null;
    }

    /**
     * Whether anyone on the instance has proven ownership of this domain.
     *
     * Instance-wide rather than per-user on purpose: a second team monitoring
     * a domain the first team verified is a colleague, not an attacker, and
     * requiring every user to re-verify the same domain only adds friction.
     */
    public function isVerified(TargetIdentity $identity): bool
    {
        if ($identity->isAddress()) {
            return false;
        }

        return VerifiedDomain::query()->verified()->where('domain', $identity->domain)->exists();
    }

    /**
     * Limits applied to a domain nobody has proven they own. Null when
     * verification is off or the domain is already verified.
     */
    public function unverifiedLimits(TargetIdentity $identity): ?UnverifiedLimits
    {
        if (! config('monitoring.abuse.require_domain_verification')) {
            return null;
        }

        return $this->isVerified($identity) ? null : UnverifiedLimits::fromConfig();
    }
}
