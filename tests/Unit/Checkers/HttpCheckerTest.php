<?php

namespace Tests\Unit\Checkers;

use App\Checkers\HttpChecker;
use App\Enums\MonitorType;
use App\Models\Monitor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpCheckerTest extends TestCase
{
    private function monitor(array $config = []): Monitor
    {
        return new Monitor([
            'name' => 'Site',
            'url' => 'https://example.com',
            'type' => MonitorType::Http,
            'timeout' => 5,
            'config' => $config,
        ]);
    }

    public function test_a_successful_response_is_up(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $result = (new HttpChecker)->check($this->monitor());

        $this->assertTrue($result->isUp);
        $this->assertNull($result->error);
        $this->assertSame(200, $result->meta['status_code']);
    }

    public function test_a_redirect_status_is_still_up(): void
    {
        Http::fake(['*' => Http::response('', 304)]);

        $this->assertTrue((new HttpChecker)->check($this->monitor())->isUp);
    }

    public function test_a_server_error_is_down(): void
    {
        Http::fake(['*' => Http::response('boom', 503)]);

        $result = (new HttpChecker)->check($this->monitor());

        $this->assertFalse($result->isUp);
        $this->assertSame('HTTP 503', $result->error);
        $this->assertSame(503, $result->meta['status_code']);
    }

    public function test_an_explicit_expected_status_must_match_exactly(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $result = (new HttpChecker)->check($this->monitor(['expected_status' => 201]));

        $this->assertFalse($result->isUp);
        $this->assertSame('Expected HTTP 201, got 200', $result->error);
    }

    public function test_an_explicit_expected_status_allows_non_2xx_codes(): void
    {
        Http::fake(['*' => Http::response('', 418)]);

        $this->assertTrue((new HttpChecker)->check($this->monitor(['expected_status' => 418]))->isUp);
    }

    public function test_a_connection_exception_is_reported_as_down(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $result = (new HttpChecker)->check($this->monitor());

        $this->assertFalse($result->isUp);
        $this->assertSame('Connection timed out', $result->error);
    }

    public function test_it_uses_the_configured_http_method(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        (new HttpChecker)->check($this->monitor(['method' => 'HEAD']));

        Http::assertSent(fn ($request) => $request->method() === 'HEAD');
    }
}
