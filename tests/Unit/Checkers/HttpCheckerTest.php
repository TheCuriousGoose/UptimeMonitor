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

    public function test_an_incomplete_certificate_chain_gets_an_actionable_message(): void
    {
        Http::fake(fn () => throw new ConnectionException(
            'cURL error 60: SSL certificate problem: unable to get local issuer certificate',
        ));

        $result = (new HttpChecker)->check($this->monitor());

        $this->assertFalse($result->isUp);
        $this->assertStringContainsString('intermediate certificate', $result->error);
        $this->assertStringContainsString('Verify TLS certificate', $result->error);
    }

    public function test_an_expired_certificate_gets_an_actionable_message(): void
    {
        Http::fake(fn () => throw new ConnectionException(
            'cURL error 60: SSL certificate problem: certificate has expired',
        ));

        $this->assertSame(
            'TLS verification failed: the certificate has expired.',
            (new HttpChecker)->check($this->monitor())->error,
        );
    }

    public function test_unrecognised_errors_are_passed_through_unchanged(): void
    {
        Http::fake(fn () => throw new ConnectionException('Could not resolve host: nope.invalid'));

        $this->assertSame(
            'Could not resolve host: nope.invalid',
            (new HttpChecker)->check($this->monitor())->error,
        );
    }

    public function test_disabling_verification_skips_the_tls_check(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $result = (new HttpChecker)->check($this->monitor(['verify_ssl' => false]));

        $this->assertTrue($result->isUp);
    }

    public function test_it_uses_the_configured_http_method(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        (new HttpChecker)->check($this->monitor(['method' => 'HEAD']));

        Http::assertSent(fn ($request) => $request->method() === 'HEAD');
    }
}
