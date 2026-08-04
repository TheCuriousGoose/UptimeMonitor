<?php

namespace App\Checkers\Support;

class SystemDnsResolver implements DnsResolver
{
    private const TYPES = [
        'A' => DNS_A,
        'AAAA' => DNS_AAAA,
        'CNAME' => DNS_CNAME,
        'MX' => DNS_MX,
        'TXT' => DNS_TXT,
        'NS' => DNS_NS,
    ];

    /**
     * The key holding the useful value differs per record type.
     */
    private const VALUE_KEYS = [
        'A' => 'ip',
        'AAAA' => 'ipv6',
        'CNAME' => 'target',
        'MX' => 'target',
        'TXT' => 'txt',
        'NS' => 'target',
    ];

    public function resolve(string $host, string $recordType): array
    {
        $recordType = strtoupper($recordType);
        $records = @dns_get_record($host, self::TYPES[$recordType] ?? DNS_A);

        if ($records === false) {
            return [];
        }

        $key = self::VALUE_KEYS[$recordType] ?? 'ip';

        return array_values(array_filter(array_map(
            fn (array $record): string => (string) ($record[$key] ?? ''),
            $records,
        )));
    }
}
