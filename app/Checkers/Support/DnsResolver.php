<?php

namespace App\Checkers\Support;

interface DnsResolver
{
    /**
     * Resolve a hostname, returning the record values as plain strings.
     *
     * @return array<int, string>
     */
    public function resolve(string $host, string $recordType): array;
}
