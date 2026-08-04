<?php

namespace App\Checkers\Support;

interface CertificateReader
{
    /**
     * Read the peer certificate for a host.
     *
     * @return array{valid_from: int, valid_to: int, issuer: string, subject: string}|null
     *                                                                                     Null when no certificate could be retrieved.
     */
    public function read(string $host, int $port, float $timeout): ?array;
}
