<?php

namespace App\Checkers;

use App\Checkers\Support\SocketConnector;
use App\Checkers\Support\Stopwatch;
use App\Checkers\Support\Target;
use App\Models\Monitor;

class PortChecker extends NetworkChecker
{
    public function __construct(private readonly SocketConnector $connector) {}

    protected function probeTarget(Monitor $monitor, Target $target, Stopwatch $timer): CheckResult
    {
        // An explicitly configured port wins; otherwise fall back to one
        // embedded in the target before the type default.
        $port = (int) ($monitor->config['port']
            ?? $target->port
            ?? $monitor->resolvedConfig()['port']
            ?? 443);

        $failure = $this->connector->connect($target->host, $port, (float) $monitor->timeout);
        $ms = $timer->elapsedMs();

        $meta = ['checker' => 'port', 'host' => $target->host, 'port' => $port];

        return $failure === null
            ? CheckResult::up($ms, $meta)
            : CheckResult::down("Port {$port} unreachable: {$failure}", $ms, $meta);
    }
}
