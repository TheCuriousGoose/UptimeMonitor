<?php

namespace Tests\Unit\Checkers;

use App\Checkers\HttpChecker;
use App\Checkers\Support\DnsResolver;
use App\Enums\MonitorType;
use App\Models\Monitor;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\StubDnsResolver;
use Tests\TestCase;

class HttpCheckerOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(DnsResolver::class, new StubDnsResolver);
    }

    private function checker(): HttpChecker
    {
        return $this->app->make(HttpChecker::class);
    }

    private function monitor(array $config = [], string $url = 'https://example.com'): Monitor
    {
        return new Monitor([
            'name' => 'Site',
            'url' => $url,
            'type' => MonitorType::Http,
            'timeout' => 5,
            'config' => $config,
        ]);
    }

    public function test_custom_headers_reach_the_request(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $this->checker()->check($this->monitor([
            'headers' => ['X-Trace' => 'abc123', 'Accept' => 'application/json'],
        ]));

        Http::assertSent(fn (Request $request) => $request->hasHeader('X-Trace', 'abc123')
            && $request->hasHeader('Accept', 'application/json'));
    }

    public function test_basic_auth_is_sent(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $this->checker()->check($this->monitor([
            'auth_type' => 'basic',
            'auth_username' => 'alice',
            'auth_password' => 'hunter2',
        ]));

        Http::assertSent(fn (Request $request) => $request->hasHeader(
            'Authorization',
            'Basic '.base64_encode('alice:hunter2'),
        ));
    }

    public function test_bearer_auth_is_sent(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $this->checker()->check($this->monitor([
            'auth_type' => 'bearer',
            'auth_token' => 'tok_live_123',
        ]));

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer tok_live_123'));
    }

    public function test_a_post_sends_its_body_and_content_type(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $this->checker()->check($this->monitor([
            'method' => 'POST',
            'body' => '{"ping":true}',
            'content_type' => 'application/json',
        ]));

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->body() === '{"ping":true}'
            && $request->hasHeader('Content-Type', 'application/json'));
    }

    public function test_multiple_accepted_codes_and_ranges(): void
    {
        Http::fake(['*' => Http::response('', 418)]);

        $this->assertTrue(
            $this->checker()->check($this->monitor(['expected_status_codes' => ['200', '410-420']]))->isUp,
        );

        $this->assertFalse(
            $this->checker()->check($this->monitor(['expected_status_codes' => ['2xx']]))->isUp,
        );
    }

    public function test_redirects_are_followed_and_the_final_url_is_recorded(): void
    {
        Http::fake([
            'https://example.com' => Http::response('', 302, ['Location' => 'https://example.com/final']),
            'https://example.com/final' => Http::response('ok', 200),
        ]);

        $result = $this->checker()->check($this->monitor());

        $this->assertTrue($result->isUp);
        $this->assertSame('https://example.com/final', $result->meta['final_url']);
    }

    public function test_redirects_can_be_refused(): void
    {
        Http::fake([
            'https://example.com' => Http::response('', 302, ['Location' => 'https://example.com/final']),
        ]);

        $result = $this->checker()->check($this->monitor([
            'follow_redirects' => false,
            'expected_status_codes' => ['200'],
        ]));

        $this->assertFalse($result->isUp);
        $this->assertStringContainsString('302', $result->error);
    }

    /**
     * A redirect loop must end at the configured hop count rather than
     * spinning until the job times out.
     */
    public function test_the_hop_count_is_capped(): void
    {
        Http::fake([
            '*' => Http::response('', 302, ['Location' => 'https://example.com/next']),
        ]);

        $result = $this->checker()->check($this->monitor([
            'max_redirects' => 2,
            'expected_status_codes' => ['200'],
        ]));

        $this->assertFalse($result->isUp);
        // Initial request plus two follows.
        Http::assertSentCount(3);
    }

    public function test_a_relative_location_is_resolved_against_the_current_url(): void
    {
        Http::fake([
            'https://example.com' => Http::response('', 302, ['Location' => '/moved']),
            'https://example.com/moved' => Http::response('ok', 200),
        ]);

        $result = $this->checker()->check($this->monitor());

        $this->assertTrue($result->isUp);
        $this->assertSame('https://example.com/moved', $result->meta['final_url']);
    }

    /**
     * A 302 to another resource is fetched with GET — that is what browsers
     * and every other client do.
     */
    public function test_a_302_downgrades_the_method_to_get(): void
    {
        Http::fake([
            'https://example.com' => Http::response('', 302, ['Location' => 'https://example.com/a']),
            'https://example.com/a' => Http::response('ok', 200),
        ]);

        $this->checker()->check($this->monitor(['method' => 'POST', 'body' => 'x']));

        Http::assertSent(fn (Request $r) => $r->url() === 'https://example.com/a' && $r->method() === 'GET');
    }

    /** 307 and 308 exist precisely to preserve the method. */
    public function test_a_308_preserves_the_method(): void
    {
        Http::fake([
            'https://example.com' => Http::response('', 308, ['Location' => 'https://example.com/b']),
            'https://example.com/b' => Http::response('ok', 200),
        ]);

        $this->checker()->check($this->monitor(['method' => 'POST', 'body' => 'x']));

        Http::assertSent(fn (Request $r) => $r->url() === 'https://example.com/b' && $r->method() === 'POST');
    }
}
