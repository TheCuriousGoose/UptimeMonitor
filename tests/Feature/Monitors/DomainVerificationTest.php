<?php

namespace Tests\Feature\Monitors;

use App\Checkers\Support\DnsResolver;
use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Models\User;
use App\Models\VerifiedDomain;
use App\Monitoring\DomainVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        config(['monitoring.abuse.require_domain_verification' => true]);
    }

    private function user(): User
    {
        return User::factory()->withRole('User')->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Marketing site',
            'url' => 'https://example.com',
            'type' => MonitorType::Http->value,
            'timeout' => 10,
            'interval_seconds' => 300,
            'is_active' => true,
        ], $overrides);
    }

    private function verify(User $user, string $domain): VerifiedDomain
    {
        return VerifiedDomain::create([
            'user_id' => $user->id,
            'domain' => $domain,
            'verified_at' => now(),
        ]);
    }

    public function test_an_unverified_domain_is_held_to_a_slow_interval(): void
    {
        $this->actingAs($this->user())
            ->post(route('monitors.store'), $this->payload(['interval_seconds' => 30]))
            ->assertSessionHasErrors('interval_seconds');
    }

    public function test_a_verified_domain_may_be_checked_at_full_rate(): void
    {
        $user = $this->user();
        $this->verify($user, 'example.com');

        $this->actingAs($user)
            ->post(route('monitors.store'), $this->payload(['interval_seconds' => 30]))
            ->assertSessionHasNoErrors();
    }

    public function test_verification_by_one_account_covers_the_instance(): void
    {
        $this->verify($this->user(), 'example.com');

        $this->actingAs($this->user())
            ->post(route('monitors.store'), $this->payload(['interval_seconds' => 30]))
            ->assertSessionHasNoErrors();
    }

    public function test_an_unverified_domain_rejects_a_request_body(): void
    {
        $this->actingAs($this->user())
            ->post(route('monitors.store'), $this->payload([
                'config' => ['method' => 'GET', 'body' => '{"a":1}', 'content_type' => 'application/json'],
            ]))
            ->assertSessionHasErrors('config.body');
    }

    public function test_an_unverified_domain_rejects_a_write_method(): void
    {
        $this->actingAs($this->user())
            ->post(route('monitors.store'), $this->payload([
                'config' => ['method' => 'POST'],
            ]))
            ->assertSessionHasErrors('config.method');
    }

    public function test_an_unverified_domain_allows_one_monitor_only(): void
    {
        $user = $this->user();

        Monitor::factory()->forUser($user)->create(['url' => 'https://example.com/one']);

        $this->actingAs($user)
            ->post(route('monitors.store'), $this->payload())
            ->assertSessionHasErrors('url');
    }

    public function test_nothing_is_restricted_when_verification_is_off(): void
    {
        config(['monitoring.abuse.require_domain_verification' => false]);

        $this->actingAs($this->user())
            ->post(route('monitors.store'), $this->payload([
                'interval_seconds' => 30,
                'config' => ['method' => 'POST'],
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_a_dns_token_verifies_the_domain(): void
    {
        $user = $this->user();
        $domain = VerifiedDomain::create(['user_id' => $user->id, 'domain' => 'example.com']);

        $this->mock(DnsResolver::class, function ($mock) use ($domain) {
            $mock->shouldReceive('resolve')
                ->andReturnUsing(fn (string $host, string $type) => $host === '_vigil-verify.example.com' && $type === 'TXT'
                    ? [$domain->token]
                    : []);
        });

        $this->assertTrue(app(DomainVerifier::class)->verify($domain));
        $this->assertNotNull($domain->fresh()->verified_at);
    }

    public function test_a_missing_token_leaves_the_domain_unverified(): void
    {
        $user = $this->user();
        $domain = VerifiedDomain::create(['user_id' => $user->id, 'domain' => 'example.com']);

        $this->mock(DnsResolver::class, fn ($mock) => $mock->shouldReceive('resolve')->andReturn([]));

        $this->assertFalse(app(DomainVerifier::class)->verify($domain));
        $this->assertNull($domain->fresh()->verified_at);
        $this->assertNotNull($domain->fresh()->last_error);
    }

    public function test_a_user_cannot_verify_another_accounts_domain(): void
    {
        $domain = VerifiedDomain::create(['user_id' => $this->user()->id, 'domain' => 'example.com']);

        $this->actingAs($this->user())
            ->post(route('domains.verify', $domain))
            ->assertNotFound();
    }
}
