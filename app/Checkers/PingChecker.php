<?php

namespace App\Checkers;

use App\Checkers\Support\PingRunner;
use App\Checkers\Support\Stopwatch;
use App\Checkers\Support\Target;
use App\Models\Monitor;

class PingChecker extends NetworkChecker
{
    public function __construct(private readonly PingRunner $runner) {}

    protected function probeTarget(Monitor $monitor, Target $target, Stopwatch $timer): CheckResult
    {
        $result = $this->runner->ping($target->host, (float) $monitor->timeout);

        $ms = $result['latency_ms'] ?? $timer->elapsedMs();
        $meta = ['checker' => 'ping', 'host' => $target->host];

        return $result['reachable']
            ? CheckResult::up($ms, $meta)
            : CheckResult::down("Host {$target->host} did not respond to ping", $ms, $meta);
    }
}
