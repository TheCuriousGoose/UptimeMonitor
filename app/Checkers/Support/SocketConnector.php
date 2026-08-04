<?php

namespace App\Checkers\Support;

interface SocketConnector
{
    /**
     * Attempt a TCP connection.
     *
     * @return string|null Null on success, otherwise the failure reason.
     */
    public function connect(string $host, int $port, float $timeout): ?string;
}
