<?php

namespace Tests\Feature\Monitors;

use App\Checkers\Support\DnsResolver;
use App\Models\Monitor;
use App\Models\User;
use App\Monitoring\ConfigMasker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\StubDnsResolver;
use Tests\TestCase;

/**
 * "Test check" runs a configured-but-unsaved monitor once. It borrows the
 * write request's rules and the checkers' outbound guards, so the things worth
 * pinning are that it persists nothing and that it cannot be pointed at
 * another user's stored credentials.
 */
class MonitorPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->app->instance(DnsResolver::class, new StubDnsResolver([
            'public.test' => ['93.184.216.34'],
        ]));
    }

    private function user(): User
    {
        return User::factory()->withRole('User')->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'url' => 'https://public.test',
            'type' => 'http',
            'timeout' => 10,
            'config' => [],
        ], $overrides);
    }

    public function test_it_reports_a_healthy_check(): void
    {
        Http::fake(['*' => Http::response('hello', 200)]);

        $this->actingAs($this->user())
            ->postJson(route('monitors.preview'), $this->payload())
            ->assertOk()
            ->assertJson(['is_up' => true, 'status_code' => 200]);
    }

    public function test_it_reports_a_failing_check_without_erroring(): void
    {
        Http::fake(['*' => Http::response('nope', 500)]);

        $this->actingAs($this->user())
            ->postJson(route('monitors.preview'), $this->payload())
            ->assertOk()
            ->assertJson(['is_up' => false, 'status_code' => 500]);
    }

    public function test_a_keyword_check_reports_the_missing_text(): void
    {
        Http::fake(['*' => Http::response('nothing to see', 200)]);

        $response = $this->actingAs($this->user())
            ->postJson(route('monitors.preview'), $this->payload([
                'type' => 'keyword',
                'config' => ['keyword' => 'All systems operational'],
            ]))
            ->assertOk();

        $this->assertFalse($response->json('is_up'));
        $this->assertNotNull($response->json('error'));
    }

    public function test_it_saves_nothing(): void
    {
        Http::fake(['*' => Http::response('hello', 200)]);

        $this->actingAs($this->user())
            ->postJson(route('monitors.preview'), $this->payload())
            ->assertOk();

        $this->assertDatabaseCount('monitors', 0);
        $this->assertDatabaseCount('monitor_checks', 0);
        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_it_validates_the_payload_the_same_way_a_save_does(): void
    {
        $this->actingAs($this->user())
            ->postJson(route('monitors.preview'), $this->payload([
                'url' => 'not a url',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('url');
    }

    public function test_a_keyword_check_still_requires_its_keyword(): void
    {
        $this->actingAs($this->user())
            ->postJson(route('monitors.preview'), $this->payload(['type' => 'keyword']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('config.keyword');
    }

    public function test_a_masked_credential_is_resolved_from_the_named_monitor(): void
    {
        Http::fake(['*' => Http::response('hello', 200)]);

        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create([
            'url' => 'https://public.test',
            'config' => ['auth_type' => 'bearer', 'auth_token' => 'real-token'],
        ]);

        $this->actingAs($user)
            ->postJson(route('monitors.preview'), $this->payload([
                'monitor' => $monitor->uuid,
                'config' => [
                    'auth_type' => 'bearer',
                    'auth_token' => ConfigMasker::MASK,
                ],
            ]))
            ->assertOk();

        Http::assertSent(
            fn ($request) => $request->hasHeader('Authorization', 'Bearer real-token'),
        );
    }

    public function test_it_will_not_unmask_against_someone_elses_monitor(): void
    {
        Http::fake(['*' => Http::response('hello', 200)]);

        $theirs = Monitor::factory()->forUser($this->user())->create([
            'config' => ['auth_type' => 'bearer', 'auth_token' => 'their-token'],
        ]);

        $this->actingAs($this->user())
            ->postJson(route('monitors.preview'), $this->payload([
                'monitor' => $theirs->uuid,
                'config' => [
                    'auth_type' => 'bearer',
                    'auth_token' => ConfigMasker::MASK,
                ],
            ]))
            ->assertOk();

        Http::assertSent(
            fn ($request) => ! $request->hasHeader('Authorization', 'Bearer their-token'),
        );
    }

    public function test_credentials_still_cannot_be_pointed_at_a_private_host(): void
    {
        $this->app->instance(DnsResolver::class, new StubDnsResolver([
            'internal.test' => ['10.0.0.5'],
        ]));

        $this->actingAs($this->user())
            ->postJson(route('monitors.preview'), $this->payload([
                'url' => 'https://internal.test',
                'config' => ['auth_type' => 'bearer', 'auth_token' => 'secret'],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('url');
    }

    public function test_a_guest_cannot_run_a_check(): void
    {
        $this->postJson(route('monitors.preview'), $this->payload())
            ->assertUnauthorized();
    }
}
