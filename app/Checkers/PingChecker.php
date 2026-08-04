<?php

namespace App\Checkers;

use App\Checkers\Support\PingRunner;
use App\Checkers\Support\Target;
use App\Models\Monitor;

class PingChecker implements Checker
{
    public function __construct(private readonly PingRunner $runner) {}

    public function check(Monitor $monitor): CheckResult
    {
        $target = Target::parse($monitor->url);

        $start = hrtime(true);
        $result = $this->runner->ping($target->host, (float) $monitor->timeout);
        $elapsed = (int) ((hrtime(true) - $start) / 1_000_000);

        $ms = $result['latency_ms'] ?? $elapsed;
        $meta = ['checker' => 'ping', 'host' => $target->host];

        return $result['reachable']
            ? CheckResult::up($ms, $meta)
            : CheckResult::down("Host {$target->host} did not respond to ping", $ms, $meta);
    }

    public function queue(): string
    {
        return 'checks-network';
    }
}
