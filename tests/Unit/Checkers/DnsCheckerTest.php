<?php

namespace Tests\Unit\Checkers;

use App\Checkers\DnsChecker;
use App\Checkers\Support\DnsResolver;
use App\Enums\MonitorType;
use App\Models\Monitor;
use Tests\TestCase;

class DnsCheckerTest extends TestCase
{
    private function resolver(array $records, array &$calls = []): DnsResolver
    {
        return new class($records, $calls) implements DnsResolver
        {
            public function __construct(private array $records, private array &$calls) {}

            public function resolve(string $host, string $recordType): array
            {
                $this->calls[] = compact('host', 'recordType');

                return $this->records;
            }
        };
    }

    private function monitor(array $config): Monitor
    {
        return new Monitor([
            'name' => 'DNS',
            'url' => 'example.com',
            'type' => MonitorType::Dns,
            'timeout' => 5,
            'config' => $config,
        ]);
    }

    public function test_it_is_up_when_records_resolve(): void
    {
        $result = (new DnsChecker($this->resolver(['93.184.216.34'])))
            ->check($this->monitor(['record_type' => 'A']));

        $this->assertTrue($result->isUp);
        $this->assertSame(['93.184.216.34'], $result->meta['records']);
    }

    public function test_it_is_down_when_nothing_resolves(): void
    {
        $result = (new DnsChecker($this->resolver([])))
            ->check($this->monitor(['record_type' => 'A']));

        $this->assertFalse($result->isUp);
        $this->assertSame('No A records found for example.com', $result->error);
    }

    public function test_it_is_up_when_the_expected_value_is_among_the_records(): void
    {
        $result = (new DnsChecker($this->resolver(['1.1.1.1', '2.2.2.2'])))
            ->check($this->monitor(['record_type' => 'A', 'expected' => '2.2.2.2']));

        $this->assertTrue($result->isUp);
    }

    public function test_it_is_down_when_the_expected_value_is_missing(): void
    {
        $result = (new DnsChecker($this->resolver(['1.1.1.1'])))
            ->check($this->monitor(['record_type' => 'A', 'expected' => '9.9.9.9']));

        $this->assertFalse($result->isUp);
        $this->assertSame('Expected A record "9.9.9.9", got 1.1.1.1', $result->error);
    }

    public function test_the_record_type_is_passed_through_uppercased(): void
    {
        $calls = [];
        (new DnsChecker($this->resolver(['mail.example.com'], $calls)))
            ->check($this->monitor(['record_type' => 'mx']));

        $this->assertSame('MX', $calls[0]['recordType']);
        $this->assertSame('example.com', $calls[0]['host']);
    }

    public function test_a_url_target_is_reduced_to_its_hostname(): void
    {
        $calls = [];
        $monitor = $this->monitor(['record_type' => 'A']);
        $monitor->url = 'https://sub.example.com/path';

        (new DnsChecker($this->resolver(['1.1.1.1'], $calls)))->check($monitor);

        $this->assertSame('sub.example.com', $calls[0]['host']);
    }
}
