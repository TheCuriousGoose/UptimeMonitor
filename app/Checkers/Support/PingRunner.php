<?php

namespace App\Checkers\Support;

interface PingRunner
{
    /**
     * Send a single ICMP echo request.
     *
     * @return array{reachable: bool, latency_ms: int|null, output: string}
     */
    public function ping(string $host, float $timeout): array;
}
