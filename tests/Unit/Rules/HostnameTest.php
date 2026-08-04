<?php

namespace Tests\Unit\Rules;

use App\Rules\Hostname;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HostnameTest extends TestCase
{
    private function fails(mixed $value): bool
    {
        $failed = false;

        (new Hostname)->validate('url', $value, function () use (&$failed) {
            $failed = true;

            // The rule calls ->translate() on the returned message.
            return new class
            {
                public function translate(): void {}
            };
        });

        return $failed;
    }

    #[DataProvider('validHosts')]
    public function test_it_accepts_hostnames_and_ips(string $value): void
    {
        $this->assertFalse($this->fails($value), "Expected [{$value}] to be accepted.");
    }

    #[DataProvider('invalidHosts')]
    public function test_it_rejects_anything_else(mixed $value): void
    {
        $this->assertTrue($this->fails($value), 'Expected the value to be rejected.');
    }

    public static function validHosts(): array
    {
        return [
            ['example.com'],
            ['sub.example.com'],
            ['example.com:8080'],
            ['127.0.0.1'],
            ['127.0.0.1:3306'],
            ['2001:db8::1'],
        ];
    }

    public static function invalidHosts(): array
    {
        return [
            'url' => ['https://example.com'],
            'empty' => [''],
            'whitespace' => ['   '],
            'no dot' => ['localhost'],
            'not a string' => [123],
            'spaces' => ['not a host'],
        ];
    }
}
