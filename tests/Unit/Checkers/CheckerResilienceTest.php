<?php

namespace Tests\Unit\Checkers;

use App\Checkers\DnsChecker;
use App\Checkers\PingChecker;
use App\Checkers\PortChecker;
use App\Checkers\SslChecker;
use App\Checkers\Support\CertificateReader;
use App\Checkers\Support\DnsResolver;
use App\Checkers\Support\PingRunner;
use App\Checkers\Support\SocketConnector;
use App\Enums\MonitorType;
use App\Models\Monitor;
use RuntimeException;
use Tests\TestCase;

/**
 * RunMonitorCheck sets tries = 1, so an exception escaping a checker is not
 * retried — it loses the check outright: no row written, no status change,
 * and next_check_at left where it was. A probe that blows up must still come
 * back as a failed check.
 */
class CheckerResilienceTest extends TestCase
{
    private function monitor(MonitorType $type, array $config = []): Monitor
    {
        return new Monitor([
            'name' => 'Target',
            'url' => 'example.com',
            'type' => $type,
            'timeout' => 5,
            'config' => $config,
        ]);
    }

    public function test_a_throwing_certificate_reader_is_reported_as_down(): void
    {
        $reader = new class implements CertificateReader
        {
            public function read(string $host, int $port, float $timeout): ?array
            {
                throw new RuntimeException('TLS handshake exploded');
            }
        };

        $result = (new SslChecker($reader))->check($this->monitor(MonitorType::Ssl));

        $this->assertFalse($result->isUp);
        $this->assertSame('TLS handshake exploded', $result->error);
        $this->assertSame('ssl', $result->meta['checker']);
    }

    public function test_a_throwing_ping_runner_is_reported_as_down(): void
    {
        $runner = new class implements PingRunner
        {
            public function ping(string $host, float $timeout): array
            {
                throw new RuntimeException('ping binary missing');
            }
        };

        $result = (new PingChecker($runner))->check($this->monitor(MonitorType::Ping));

        $this->assertFalse($result->isUp);
        $this->assertSame('ping binary missing', $result->error);
    }

    public function test_a_throwing_dns_resolver_is_reported_as_down(): void
    {
        $resolver = new class implements DnsResolver
        {
            public function resolve(string $host, string $recordType): array
            {
                throw new RuntimeException('resolver unavailable');
            }
        };

        $result = (new DnsChecker($resolver))->check($this->monitor(MonitorType::Dns, ['record_type' => 'A']));

        $this->assertFalse($result->isUp);
        $this->assertSame('resolver unavailable', $result->error);
    }

    public function test_a_throwing_socket_connector_is_reported_as_down(): void
    {
        $connector = new class implements SocketConnector
        {
            public function connect(string $host, int $port, float $timeout): ?string
            {
                throw new RuntimeException('socket layer failed');
            }
        };

        $result = (new PortChecker($connector))->check($this->monitor(MonitorType::Port, ['port' => 5432]));

        $this->assertFalse($result->isUp);
        $this->assertSame('socket layer failed', $result->error);
    }
}
