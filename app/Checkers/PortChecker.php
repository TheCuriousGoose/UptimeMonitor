<?php

namespace App\Checkers;

use App\Checkers\Support\SocketConnector;
use App\Checkers\Support\Target;
use App\Models\Monitor;

class PortChecker implements Checker
{
    public function __construct(private readonly SocketConnector $connector) {}

    public function check(Monitor $monitor): CheckResult
    {
        $target = Target::parse($monitor->url);

        // An explicitly configured port wins; otherwise fall back to one
        // embedded in the target before the type default.
        $port = (int) ($monitor->config['port']
            ?? $target->port
            ?? $monitor->resolvedConfig()['port']
            ?? 443);

        $start = hrtime(true);
        $failure = $this->connector->connect($target->host, $port, (float) $monitor->timeout);
        $ms = (int) ((hrtime(true) - $start) / 1_000_000);

        $meta = ['checker' => 'port', 'host' => $target->host, 'port' => $port];

        return $failure === null
            ? CheckResult::up($ms, $meta)
            : CheckResult::down("Port {$port} unreachable: {$failure}", $ms, $meta);
    }

    public function queue(): string
    {
        return 'checks-network';
    }
}
