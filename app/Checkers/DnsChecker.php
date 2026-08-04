<?php

namespace App\Checkers;

use App\Checkers\Support\DnsResolver;
use App\Checkers\Support\Target;
use App\Models\Monitor;

class DnsChecker implements Checker
{
    public function __construct(private readonly DnsResolver $resolver) {}

    public function check(Monitor $monitor): CheckResult
    {
        $config = $monitor->resolvedConfig();
        $target = Target::parse($monitor->url);
        $recordType = strtoupper((string) ($config['record_type'] ?? 'A'));

        $start = hrtime(true);
        $records = $this->resolver->resolve($target->host, $recordType);
        $ms = (int) ((hrtime(true) - $start) / 1_000_000);

        $meta = ['checker' => 'dns', 'record_type' => $recordType, 'records' => $records];

        if ($records === []) {
            return CheckResult::down("No {$recordType} records found for {$target->host}", $ms, $meta);
        }

        $expected = $config['expected'] ?? null;

        if ($expected !== null && $expected !== '' && ! in_array($expected, $records, true)) {
            return CheckResult::down(
                "Expected {$recordType} record \"{$expected}\", got ".implode(', ', $records),
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
