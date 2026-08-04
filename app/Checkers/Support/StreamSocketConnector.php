<?php

namespace App\Checkers\Support;

class StreamSocketConnector implements SocketConnector
{
    public function connect(string $host, int $port, float $timeout): ?string
    {
        $errorNumber = 0;
        $errorMessage = '';

        $socket = @fsockopen($host, $port, $errorNumber, $errorMessage, $timeout);

        if ($socket === false) {
            return $errorMessage !== '' ? $errorMessage : "Could not connect to {$host}:{$port}";
        }

        fclose($socket);

        return null;
    }
}
