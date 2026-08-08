<?php

namespace App\Checkers;

use App\Checkers\Support\Stopwatch;
use App\Checkers\Support\Target;
use App\Models\Monitor;

/**
 * A check against a host rather than a URL.
 *
 * These types store a bare hostname (or a host:port) instead of a full URL,
 * so each one began by normalising the target the same way. They share a
 * queue for the same reason: they are cheap, and none of them speaks HTTP.
 */
abstract class NetworkChecker extends BaseChecker
{
    public function queue(): string
    {
        return 'checks-network';
    }

    protected function probe(Monitor $monitor, Stopwatch $timer): CheckResult
    {
        return $this->probeTarget($monitor, Target::parse($monitor->url), $timer);
    }

    abstract protected function probeTarget(Monitor $monitor, Target $target, Stopwatch $timer): CheckResult;
}
