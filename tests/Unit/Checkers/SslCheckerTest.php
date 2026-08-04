<?php

namespace Tests\Unit\Checkers;

use App\Checkers\SslChecker;
use App\Checkers\Support\CertificateReader;
use App\Enums\MonitorType;
use App\Models\Monitor;
use Tests\TestCase;

class SslCheckerTest extends TestCase
{
    private function reader(?array $certificate, array &$calls = []): CertificateReader
    {
        return new class($certificate, $calls) implements CertificateReader
        {
            public function __construct(private ?array $certificate, private array &$calls) {}

            public function read(string $host, int $port, float $timeout): ?array
            {
                $this->calls[] = compact('host', 'port', 'timeout');

                return $this->certificate;
            }
        };
    }

    private function certificate(int $daysUntilExpiry): array
    {
        return [
            'valid_from' => now()->subDays(30)->getTimestamp(),
            'valid_to' => now()->addDays($daysUntilExpiry)->addHour()->getTimestamp(),
            'issuer' => "Let's Encrypt",
            'subject' => 'example.com',
        ];
    }

    private function monitor(array $config = []): Monitor
    {
        return new Monitor([
            'name' => 'Cert',
            'url' => 'https://example.com',
            'type' => MonitorType::Ssl,
            'timeout' => 5,
            'config' => $config,
        ]);
    }

    public function test_a_healthy_certificate_is_up(): void
    {
        $result = (new SslChecker($this->reader($this->certificate(60))))
            ->check($this->monitor(['warn_days' => 14]));

        $this->assertTrue($result->isUp);
        $this->assertSame(60, $result->meta['days_remaining']);
        $this->assertSame("Let's Encrypt", $result->meta['issuer']);
    }

    public function test_a_certificate_inside_the_warning_window_is_down(): void
    {
        $result = (new SslChecker($this->reader($this->certificate(5))))
            ->check($this->monitor(['warn_days' => 14]));

        $this->assertFalse($result->isUp);
        $this->assertSame('Certificate expires in 5 day(s)', $result->error);
    }

    public function test_an_expired_certificate_is_down(): void
    {
        $certificate = $this->certificate(0);
        $certificate['valid_to'] = now()->subDays(3)->getTimestamp();

        $result = (new SslChecker($this->reader($certificate)))->check($this->monitor());

        $this->assertFalse($result->isUp);
        $this->assertSame('Certificate expired 3 day(s) ago', $result->error);
    }

    public function test_an_unreadable_certificate_is_down(): void
    {
        $result = (new SslChecker($this->reader(null)))->check($this->monitor());

        $this->assertFalse($result->isUp);
        $this->assertSame('Could not read a TLS certificate for example.com:443', $result->error);
    }

    public function test_it_defaults_to_port_443_and_honours_an_explicit_port(): void
    {
        $calls = [];
        $checker = new SslChecker($this->reader($this->certificate(90), $calls));

        $checker->check($this->monitor());

        $withPort = $this->monitor();
        $withPort->url = 'https://example.com:8443';
        $checker->check($withPort);

        $this->assertSame(443, $calls[0]['port']);
        $this->assertSame(8443, $calls[1]['port']);
    }
}
