<?php

namespace Tests\Unit\Checkers;

use App\Checkers\Support\Target;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TargetTest extends TestCase
{
    #[DataProvider('targets')]
    public function test_it_parses_targets(string $input, string $host, ?int $port, string $scheme): void
    {
        $target = Target::parse($input);

        $this->assertSame($host, $target->host);
        $this->assertSame($port, $target->port);
        $this->assertSame($scheme, $target->scheme);
    }

    public static function targets(): array
    {
        return [
            'bare host' => ['example.com', 'example.com', null, ''],
            'host with port' => ['example.com:8080', 'example.com', 8080, ''],
            'https url' => ['https://example.com/health', 'example.com', null, 'https'],
            'url with port' => ['https://example.com:8443/x', 'example.com', 8443, 'https'],
            'ipv4' => ['127.0.0.1', '127.0.0.1', null, ''],
            'ipv4 with port' => ['127.0.0.1:3306', '127.0.0.1', 3306, ''],
            'trailing slash' => ['example.com/', 'example.com', null, ''],
            'whitespace' => ['  example.com  ', 'example.com', null, ''],
        ];
    }

    public function test_it_leaves_ipv6_literals_intact(): void
    {
        $target = Target::parse('2001:db8::1');

        $this->assertSame('2001:db8::1', $target->host);
        $this->assertNull($target->port);
    }

    public function test_port_or_returns_the_default_when_absent(): void
    {
        $this->assertSame(443, Target::parse('example.com')->portOr(443));
        $this->assertSame(8080, Target::parse('example.com:8080')->portOr(443));
    }
}
