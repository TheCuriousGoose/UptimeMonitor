<?php

namespace Tests\Unit\Checkers;

use App\Checkers\KeywordChecker;
use App\Checkers\Support\DnsResolver;
use App\Enums\MonitorType;
use App\Models\Monitor;
use Illuminate\Support\Facades\Http;
use Tests\Support\StubDnsResolver;
use Tests\TestCase;

class KeywordCheckerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A fixed public answer: these tests are about the checker, not about
        // what example.com resolves to from this machine today.
        $this->app->instance(DnsResolver::class, new StubDnsResolver);
    }

    private function checker(): KeywordChecker
    {
        return $this->app->make(KeywordChecker::class);
    }

    private function monitor(array $config): Monitor
    {
        return new Monitor([
            'name' => 'Site',
            'url' => 'https://example.com',
            'type' => MonitorType::Keyword,
            'timeout' => 5,
            'config' => $config,
        ]);
    }

    public function test_it_is_up_when_the_keyword_is_present(): void
    {
        Http::fake(['*' => Http::response('<h1>All systems operational</h1>', 200)]);

        $result = $this->checker()->check($this->monitor(['keyword' => 'operational']));

        $this->assertTrue($result->isUp);
    }

    public function test_it_is_down_when_the_keyword_is_missing_despite_a_200(): void
    {
        Http::fake(['*' => Http::response('<h1>Something went wrong</h1>', 200)]);

        $result = $this->checker()->check($this->monitor(['keyword' => 'operational']));

        $this->assertFalse($result->isUp);
        $this->assertSame('Keyword "operational" not found in response body', $result->error);
    }

    public function test_inverted_mode_fails_when_the_keyword_appears(): void
    {
        Http::fake(['*' => Http::response('Fatal error: undefined', 200)]);

        $result = $this->checker()->check(
            $this->monitor(['keyword' => 'Fatal error', 'invert' => true]),
        );

        $this->assertFalse($result->isUp);
        $this->assertSame('Keyword "Fatal error" was found but should be absent', $result->error);
    }

    public function test_inverted_mode_passes_when_the_keyword_is_absent(): void
    {
        Http::fake(['*' => Http::response('Everything is fine', 200)]);

        $result = $this->checker()->check(
            $this->monitor(['keyword' => 'Fatal error', 'invert' => true]),
        );

        $this->assertTrue($result->isUp);
    }

    public function test_the_status_code_is_checked_before_the_body(): void
    {
        Http::fake(['*' => Http::response('operational', 500)]);

        $result = $this->checker()->check($this->monitor(['keyword' => 'operational']));

        $this->assertFalse($result->isUp);
        $this->assertSame('HTTP 500', $result->error);
    }
}
