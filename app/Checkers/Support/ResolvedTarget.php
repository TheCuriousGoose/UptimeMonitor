<?php

namespace App\Checkers\Support;

/**
 * A hostname together with the single address the guard vetted for it.
 */
final readonly class ResolvedTarget
{
    public function __construct(
        public string $host,
        public string $address,
        public int $port,
    ) {}

    /**
     * cURL's CURLOPT_RESOLVE entry, which pre-seeds the DNS cache so the
     * connection uses the address that was actually checked rather than
     * resolving again.
     */
    public function curlResolveEntry(): string
    {
        return "{$this->host}:{$this->port}:{$this->address}";
    }
}
