<?php

namespace Tests\Feature\Monitors;

use App\Checkers\CheckResult;
use App\Enums\MonitorType;
use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use App\Models\User;
use App\Monitoring\EgressBreaker;
use App\Monitoring\StatusEvaluator;
use App\Monitoring\TargetIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Scheduled checks are dispatched by cron, so none of the HTTP rate limiters
 * apply to them. These cover the controls that do.
 */
class AbuseControlsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
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

    public function test_the_target_domain_is_derived_on_save(): void
    {
        $monitor = Monitor::factory()->create(['url' => 'https://A.Deep.Example.co.uk/status?x=1']);

        $this->assertSame('example.co.uk', $monitor->fresh()->target_domain);
    }

    public function test_it_caps_how_many_monitors_one_account_can_own(): void
    {
        config(['monitoring.abuse.max_monitors_per_user' => 1]);

        $user = $this->user();
        Monitor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->post(route('monitors.store'), $this->payload())
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Monitor::query()->where('created_by', $user->id)->count());
    }

    public function test_it_caps_the_request_rate_against_one_domain_per_user(): void
    {
        config(['monitoring.abuse.max_requests_per_minute_per_domain_per_user' => 2]);

        $user = $this->user();

        Monitor::factory()->forUser($user)->create([
            'url' => 'https://example.com/one',
            'interval_seconds' => 30,
        ]);

        $this->actingAs($user)
            ->post(route('monitors.store'), $this->payload(['interval_seconds' => 30]))
            ->assertSessionHasErrors('interval_seconds');
    }

    public function test_a_slower_interval_fits_inside_the_same_budget(): void
    {
        config(['monitoring.abuse.max_requests_per_minute_per_domain_per_user' => 2]);

        $user = $this->user();

        Monitor::factory()->forUser($user)->create([
            'url' => 'https://example.com/one',
            'interval_seconds' => 60,
        ]);

        $this->actingAs($user)
            ->post(route('monitors.store'), $this->payload(['interval_seconds' => 60]))
            ->assertSessionHasNoErrors();
    }

    public function test_subdomains_share_one_domain_budget(): void
    {
        config(['monitoring.abuse.max_requests_per_minute_per_domain_per_user' => 2]);

        $user = $this->user();

        Monitor::factory()->forUser($user)->create([
            'url' => 'https://a.example.com',
            'interval_seconds' => 30,
        ]);

        $this->actingAs($user)
            ->post(route('monitors.store'), $this->payload([
                'url' => 'https://b.example.com',
                'interval_seconds' => 30,
            ]))
            ->assertSessionHasErrors('interval_seconds');
    }

    public function test_the_instance_budget_spans_accounts(): void
    {
        config(['monitoring.abuse.max_requests_per_minute_per_domain' => 2]);

        Monitor::factory()->forUser($this->user())->create([
            'url' => 'https://example.com/one',
            'interval_seconds' => 30,
        ]);

        $this->actingAs($this->user())
            ->post(route('monitors.store'), $this->payload(['interval_seconds' => 30]))
            ->assertSessionHasErrors('interval_seconds');
    }

    public function test_no_limits_are_applied_by_default(): void
    {
        $user = $this->user();

        Monitor::factory()->count(5)->forUser($user)->create([
            'url' => 'https://example.com',
            'interval_seconds' => 30,
        ]);

        $this->actingAs($user)
            ->post(route('monitors.store'), $this->payload(['interval_seconds' => 30]))
            ->assertSessionHasNoErrors();
    }

    public function test_the_breaker_skips_a_check_once_the_domain_budget_is_spent(): void
    {
        config(['monitoring.abuse.max_requests_per_minute_per_domain' => 1]);

        $monitor = Monitor::factory()->create(['url' => 'https://example.com']);
        $identity = TargetIdentity::forMonitor($monitor);
        $breaker = app(EgressBreaker::class);

        $this->assertTrue($breaker->attempt($identity));
        $this->assertFalse($breaker->attempt($identity));
    }

    public function test_a_throttled_check_records_nothing(): void
    {
        config(['monitoring.abuse.max_requests_per_minute_per_domain' => 1]);

        $monitor = Monitor::factory()->create(['url' => 'https://example.com']);

        app(EgressBreaker::class)->attempt(TargetIdentity::forMonitor($monitor));

        Http::fake();

        app()->call([new RunMonitorCheck($monitor), 'handle']);

        Http::assertNothingSent();
        $this->assertSame(0, $monitor->checks()->count());
    }

    public function test_repeated_refusals_pause_the_monitor(): void
    {
        config(['monitoring.abuse.refusals_before_pause' => 3]);

        $monitor = Monitor::factory()->create(['url' => 'https://example.com']);
        $evaluator = app(StatusEvaluator::class);

        foreach (range(1, 3) as $ignored) {
            $evaluator->record($monitor, CheckResult::down('HTTP 429', 10, [
                'status_code' => 429,
                'checker' => 'http',
            ]));
        }

        $monitor->refresh();

        $this->assertFalse((bool) $monitor->is_active);
        $this->assertNotNull($monitor->paused_at);
        $this->assertStringContainsString('429', (string) $monitor->paused_reason);
    }

    public function test_an_ordinary_failure_does_not_pause_the_monitor(): void
    {
        config(['monitoring.abuse.refusals_before_pause' => 2]);

        $monitor = Monitor::factory()->create(['url' => 'https://example.com']);
        $evaluator = app(StatusEvaluator::class);

        foreach (range(1, 5) as $ignored) {
            $evaluator->record($monitor, CheckResult::down('HTTP 500', 10, [
                'status_code' => 500,
                'checker' => 'http',
            ]));
        }

        $this->assertTrue((bool) $monitor->fresh()->is_active);
    }

    public function test_a_success_clears_the_refusal_streak(): void
    {
        config(['monitoring.abuse.refusals_before_pause' => 3]);

        $monitor = Monitor::factory()->create(['url' => 'https://example.com']);
        $evaluator = app(StatusEvaluator::class);

        $evaluator->record($monitor, CheckResult::down('HTTP 429', 10, ['status_code' => 429]));
        $evaluator->record($monitor, CheckResult::up(10, ['status_code' => 200]));

        $this->assertSame(0, (int) $monitor->fresh()->refusal_streak);
    }
}
