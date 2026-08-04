<?php

namespace Tests\Unit\Checkers;

use App\Checkers\PingChecker;
use App\Checkers\Support\PingRunner;
use App\Enums\MonitorType;
use App\Models\Monitor;
use Tests\TestCase;

class PingCheckerTest extends TestCase
{
    private function runner(bool $reachable, ?int $latency): PingRunner
    {
        return new class($reachable, $latency) implements PingRunner
        {
            public function __construct(private bool $reachable, private ?int $latency) {}

            public function ping(string $host, float $timeout): array
            {
                return [
                    'reachable' => $this->reachable,
                    'latency_ms' => $this->latency,
                    'output' => 'fake',
                ];
            }
        };
    }

    private function monitor(): Monitor
    {
        return new Monitor([
            'name' => 'Router',
            'url' => 'example.com',
            'type' => MonitorType::Ping,
            'timeout' => 5,
            'config' => [],
        ]);
    }

    public function test_a_reachable_host_is_up_and_reports_latency(): void
    {
        $result = (new PingChecker($this->runner(true, 24)))->check($this->monitor());

        $this->assertTrue($result->isUp);
        $this->assertSame(24, $result->responseMs);
        $this->assertSame('example.com', $result->meta['host']);
    }

    public function test_an_unreachable_host_is_down(): void
    {
        $result = (new PingChecker($this->runner(false, null)))->check($this->monitor());

        $this->assertFalse($result->isUp);
        $this->assertSame('Host example.com did not respond to ping', $result->error);
    }

    public function test_it_falls_back_to_wall_clock_time_without_a_parsed_latency(): void
    {
        $result = (new PingChecker($this->runner(true, null)))->check($this->monitor());

        $this->assertTrue($result->isUp);
        $this->assertGreaterThanOrEqual(0, $result->responseMs);
    }
}
