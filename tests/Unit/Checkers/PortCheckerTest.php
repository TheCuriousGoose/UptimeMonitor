<?php

namespace Tests\Unit\Checkers;

use App\Checkers\PortChecker;
use App\Checkers\Support\SocketConnector;
use App\Enums\MonitorType;
use App\Models\Monitor;
use Tests\TestCase;

class PortCheckerTest extends TestCase
{
    private function connector(?string $failure, array &$calls = []): SocketConnector
    {
        return new class($failure, $calls) implements SocketConnector
        {
            public function __construct(private ?string $failure, private array &$calls) {}

            public function connect(string $host, int $port, float $timeout): ?string
            {
                $this->calls[] = compact('host', 'port', 'timeout');

                return $this->failure;
            }
        };
    }

    private function monitor(string $url, array $config = []): Monitor
    {
        return new Monitor([
            'name' => 'DB',
            'url' => $url,
            'type' => MonitorType::Port,
            'timeout' => 5,
            'config' => $config,
        ]);
    }

    public function test_a_reachable_port_is_up(): void
    {
        $calls = [];
        $checker = new PortChecker($this->connector(null, $calls));

        $result = $checker->check($this->monitor('db.example.com', ['port' => 5432]));

        $this->assertTrue($result->isUp);
        $this->assertSame('db.example.com', $calls[0]['host']);
        $this->assertSame(5432, $calls[0]['port']);
        $this->assertSame(5.0, $calls[0]['timeout']);
        $this->assertSame(5432, $result->meta['port']);
    }

    public function test_an_unreachable_port_is_down(): void
    {
        $calls = [];
        $checker = new PortChecker($this->connector('Connection refused', $calls));

        $result = $checker->check($this->monitor('db.example.com', ['port' => 5432]));

        $this->assertFalse($result->isUp);
        $this->assertSame('Port 5432 unreachable: Connection refused', $result->error);
    }

    public function test_a_port_in_the_target_is_used_when_config_omits_one(): void
    {
        $calls = [];
        $checker = new PortChecker($this->connector(null, $calls));

        $checker->check($this->monitor('db.example.com:6379'));

        $this->assertSame(6379, $calls[0]['port']);
    }

    public function test_it_runs_on_the_network_queue(): void
    {
        $calls = [];

        $this->assertSame('checks-network', (new PortChecker($this->connector(null, $calls)))->queue());
    }
}
