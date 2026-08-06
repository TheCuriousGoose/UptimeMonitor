<?php

namespace Tests\Feature\Monitors;

use App\Checkers\HttpChecker;
use App\Checkers\Support\DnsResolver;
use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Models\User;
use App\Monitoring\ConfigMasker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\StubDnsResolver;
use Tests\TestCase;

/**
 * The checkers fetch a URL the user supplied, which makes them an SSRF
 * primitive. Monitoring a private host stays allowed — self-hosters do it on
 * purpose — but pointing credentials at one never is.
 */
class HttpCheckerSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->app->instance(DnsResolver::class, new StubDnsResolver([
            'internal.test' => ['10.0.0.5'],
            'metadata.test' => ['169.254.169.254'],
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
            'name' => 'Site',
            'url' => 'https://public.test',
            'type' => 'http',
            'timeout' => 10,
            'interval_seconds' => 300,
            'config' => [],
        ], $overrides);
    }

    public function test_the_host_header_cannot_be_overridden(): void
    {
        $this->actingAs($this->user())
            ->post(route('monitors.store'), $this->payload([
                'config' => ['headers' => ['Host' => 'admin.internal']],
            ]))
            ->assertSessionHasErrors('config.headers');
    }

    public function test_a_header_value_cannot_contain_a_line_break(): void
    {
        $this->actingAs($this->user())
            ->post(route('monitors.store'), $this->payload([
                'config' => ['headers' => ['X-Trace' => "abc\r\nHost: evil"]],
            ]))
            ->assertSessionHasErrors('config.headers.X-Trace');
    }

    public function test_the_header_count_is_capped(): void
    {
        $headers = [];

        for ($i = 0; $i < 21; $i++) {
            $headers["X-Header-{$i}"] = 'value';
        }

        $this->actingAs($this->user())
            ->post(route('monitors.store'), $this->payload(['config' => ['headers' => $headers]]))
            ->assertSessionHasErrors('config.headers');
    }

    public function test_credentials_cannot_be_pointed_at_a_private_address(): void
    {
        foreach ([
            ['headers' => ['X-Api-Key' => 'secret']],
            ['auth_type' => 'bearer', 'auth_token' => 'tok'],
            ['body' => '{"a":1}', 'content_type' => 'application/json'],
        ] as $config) {
            $this->actingAs($this->user())
                ->post(route('monitors.store'), $this->payload([
                    'url' => 'https://internal.test',
                    'config' => $config,
                ]))
                ->assertSessionHasErrors('url');
        }
    }

    /**
     * The backwards-compatible half: an existing private monitor with no
     * credentials must still save exactly as before.
     */
    public function test_a_private_target_without_credentials_still_saves(): void
    {
        $this->actingAs($this->user())
            ->post(route('monitors.store'), $this->payload(['url' => 'https://internal.test']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('monitors', ['url' => 'https://internal.test']);
    }

    public function test_a_check_refuses_a_redirect_into_link_local_space(): void
    {
        Http::fake([
            'https://public.test' => Http::response('', 302, ['Location' => 'http://metadata.test/latest/meta-data/']),
        ]);

        $monitor = new Monitor([
            'name' => 'Probe',
            'url' => 'https://public.test',
            'type' => MonitorType::Http,
            'timeout' => 5,
            'config' => ['headers' => ['Authorization' => 'Bearer tok']],
        ]);

        $result = $this->app->make(HttpChecker::class)->check($monitor);

        $this->assertFalse($result->isUp);
        $this->assertStringContainsString('private address', $result->error);
    }

    public function test_a_private_target_is_refused_entirely_when_configured(): void
    {
        config(['monitoring.outbound.allow_private_targets' => false]);

        Http::fake(['*' => Http::response('ok', 200)]);

        $monitor = new Monitor([
            'name' => 'Internal',
            'url' => 'https://internal.test',
            'type' => MonitorType::Http,
            'timeout' => 5,
            'config' => [],
        ]);

        $result = $this->app->make(HttpChecker::class)->check($monitor);

        $this->assertFalse($result->isUp);
        $this->assertStringContainsString('private address', $result->error);
    }

    public function test_a_denied_host_is_refused(): void
    {
        config(['monitoring.outbound.denied_hosts' => ['public.test']]);

        Http::fake(['*' => Http::response('ok', 200)]);

        $monitor = new Monitor([
            'name' => 'Blocked',
            'url' => 'https://public.test',
            'type' => MonitorType::Http,
            'timeout' => 5,
            'config' => [],
        ]);

        $this->assertFalse($this->app->make(HttpChecker::class)->check($monitor)->isUp);
    }

    public function test_secrets_are_masked_in_the_payload(): void
    {
        $user = $this->user();

        $monitor = Monitor::factory()->forUser($user)->create([
            'type' => MonitorType::Http,
            'config' => [
                'auth_type' => 'bearer',
                'auth_token' => 'tok_live_supersecret',
                'headers' => ['Authorization' => 'Bearer nested', 'X-Trace' => 'not-a-secret'],
            ],
        ]);

        $props = $this->actingAs($user)
            ->get(route('monitors.show', $monitor))
            ->viewData('page')['props'];

        $config = $props['monitor']['config'];

        $this->assertSame(ConfigMasker::MASK, $config['auth_token']);
        $this->assertSame(ConfigMasker::MASK, $config['headers']['Authorization']);
        $this->assertSame('not-a-secret', $config['headers']['X-Trace']);
    }

    /**
     * The trap this exists to prevent: opening a monitor to rename it posts
     * the mask back, and without unmasking that string becomes the token.
     */
    public function test_resubmitting_the_mask_leaves_the_secret_intact(): void
    {
        $user = $this->user();

        $monitor = Monitor::factory()->forUser($user)->create([
            'type' => MonitorType::Http,
            'url' => 'https://public.test',
            'config' => ['auth_type' => 'bearer', 'auth_token' => 'tok_live_supersecret'],
        ]);

        $this->actingAs($user)->put(route('monitors.update', $monitor), $this->payload([
            'name' => 'Renamed',
            'config' => ['auth_type' => 'bearer', 'auth_token' => ConfigMasker::MASK],
        ]))->assertSessionHasNoErrors();

        $monitor->refresh();

        $this->assertSame('Renamed', $monitor->name);
        $this->assertSame('tok_live_supersecret', $monitor->config['auth_token']);
    }
}
