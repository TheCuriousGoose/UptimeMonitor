<?php

namespace App\Monitoring;

/**
 * What a monitor may do against a domain nobody has proven they own: slow, few,
 * and read-only — the configurations with the most abuse value and the least
 * legitimate need against a stranger's host.
 */
final readonly class UnverifiedLimits
{
    /**
     * @param  array<int, string>  $allowedMethods
     */
    public function __construct(
        public int $minIntervalSeconds,
        public int $maxMonitorsPerDomain,
        public array $allowedMethods,
    ) {}

    public static function fromConfig(): self
    {
        $config = (array) config('monitoring.abuse.unverified');

        return new self(
            minIntervalSeconds: (int) ($config['min_interval_seconds'] ?? 300),
            maxMonitorsPerDomain: (int) ($config['max_monitors_per_domain_per_user'] ?? 1),
            allowedMethods: (array) ($config['allowed_methods'] ?? ['GET', 'HEAD']),
        );
    }

    public function allowsMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->allowedMethods, true);
    }

    public function describeMethods(): string
    {
        return implode(', ', $this->allowedMethods);
    }
}
