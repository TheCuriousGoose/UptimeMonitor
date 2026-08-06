<?php

namespace Tests\Support;

use App\Checkers\Support\DnsResolver;

/**
 * A resolver with a fixed answer, so checker tests neither hit real DNS nor
 * depend on what a public hostname happens to resolve to today.
 */
class StubDnsResolver implements DnsResolver
{
    /**
     * @param  array<string, array<int, string>>  $answers  host => addresses
     */
    public function __construct(
        private readonly array $answers = [],
        private readonly string $default = '93.184.216.34',
    ) {}

    public function resolve(string $host, string $recordType): array
    {
        if (array_key_exists($host, $this->answers)) {
            return $recordType === 'A' ? $this->answers[$host] : [];
        }

        return $recordType === 'A' ? [$this->default] : [];
    }
}
