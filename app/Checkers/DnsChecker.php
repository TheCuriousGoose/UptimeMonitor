<?php

namespace App\Checkers;

use App\Checkers\Support\DnsResolver;
use App\Checkers\Support\Stopwatch;
use App\Checkers\Support\Target;
use App\Models\Monitor;

class DnsChecker extends NetworkChecker
{
    public function __construct(private readonly DnsResolver $resolver) {}

    protected function probeTarget(Monitor $monitor, Target $target, Stopwatch $timer): CheckResult
    {
        $config = $monitor->resolvedConfig();
        $recordType = strtoupper((string) ($config['record_type'] ?? 'A'));

        $records = $this->resolver->resolve($target->host, $recordType);
        $ms = $timer->elapsedMs();

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
}
