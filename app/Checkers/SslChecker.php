<?php

namespace App\Checkers;

use App\Checkers\Support\CertificateReader;
use App\Checkers\Support\Target;
use App\Models\Monitor;

/**
 * Treats an expiring certificate as downtime, so the alert lands while
 * there is still time to renew rather than after the outage starts.
 */
class SslChecker implements Checker
{
    public function __construct(private readonly CertificateReader $reader) {}

    public function check(Monitor $monitor): CheckResult
    {
        $target = Target::parse($monitor->url);
        $port = $target->portOr(443);
        $warnDays = (int) ($monitor->resolvedConfig()['warn_days'] ?? 14);

        $start = hrtime(true);
        $certificate = $this->reader->read($target->host, $port, (float) $monitor->timeout);
        $ms = (int) ((hrtime(true) - $start) / 1_000_000);

        if ($certificate === null) {
            return CheckResult::down(
                "Could not read a TLS certificate for {$target->host}:{$port}",
                $ms,
                ['checker' => 'ssl', 'host' => $target->host],
            );
        }

        $expiresAt = $certificate['valid_to'];
        $daysRemaining = (int) floor(($expiresAt - now()->getTimestamp()) / 86400);

        $meta = [
            'checker' => 'ssl',
            'host' => $target->host,
            'issuer' => $certificate['issuer'],
            'expires_at' => date(DATE_ATOM, $expiresAt),
            'days_remaining' => $daysRemaining,
        ];

        if ($daysRemaining < 0) {
            return CheckResult::down(
                'Certificate expired '.abs($daysRemaining).' day(s) ago',
                $ms,
                $meta,
            );
        }

        if ($daysRemaining <= $warnDays) {
            return CheckResult::down(
                "Certificate expires in {$daysRemaining} day(s)",
                $ms,
                $meta,
            );
        }

        return CheckResult::up($ms, $meta);
    }

    public function queue(): string
    {
        return 'checks-network';
    }
}
