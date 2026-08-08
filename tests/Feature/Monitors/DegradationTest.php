<?php

namespace Tests\Feature\Monitors;

use App\Checkers\CheckResult;
use App\Enums\MonitorStatus;
use App\Jobs\SendAlert;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Monitoring\AlertEvent;
use App\Monitoring\StatusEvaluator;
use App\Monitoring\UptimeStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * "Up but slow" had nowhere to live: response_ms was recorded and charted but
 * never compared to anything.
 */
class DegradationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        Queue::fake();

        $this->user = User::factory()->withRole('User')->create();

        // AlertDispatcher fans out per channel, so with none configured there
        // is nothing to assert against.
        NotificationChannel::factory()->for($this->user, 'user')->create();
    }

    private function monitor(?int $threshold, int $confirmation = 1): Monitor
    {
        return Monitor::factory()->forUser($this->user)->create([
            'degraded_response_ms' => $threshold,
            'confirmation_threshold' => $confirmation,
            'recovery_threshold' => 1,
            'latest_is_up' => true,
        ]);
    }

    private function record(Monitor $monitor, int $ms, bool $up = true): void
    {
        app(StatusEvaluator::class)->record(
            $monitor,
            $up ? CheckResult::up($ms) : CheckResult::down('boom', $ms),
        );
    }

    public function test_a_slow_check_flips_the_monitor_to_degraded(): void
    {
        $monitor = $this->monitor(threshold: 1000);

        $this->record($monitor, 1500);

        $monitor->refresh();

        $this->assertTrue($monitor->is_degraded);
        $this->assertSame(MonitorStatus::Degraded, $monitor->status());
        // Still up: uptime answers "did it respond", not "how fast".
        $this->assertTrue($monitor->latest_is_up);
    }

    public function test_degradation_needs_the_confirmation_threshold(): void
    {
        $monitor = $this->monitor(threshold: 1000, confirmation: 3);

        $this->record($monitor, 1500);
        $this->assertFalse($monitor->fresh()->is_degraded);

        $this->record($monitor, 1500);
        $this->assertFalse($monitor->fresh()->is_degraded);

        $this->record($monitor, 1500);
        $this->assertTrue($monitor->fresh()->is_degraded);
    }

    public function test_a_fast_check_clears_it(): void
    {
        $monitor = $this->monitor(threshold: 1000);

        $this->record($monitor, 1500);
        $this->record($monitor, 200);

        $monitor->refresh();

        $this->assertFalse($monitor->is_degraded);
        $this->assertSame(0, $monitor->degraded_streak);
        $this->assertSame(MonitorStatus::Up, $monitor->status());
    }

    public function test_a_null_threshold_never_degrades(): void
    {
        $monitor = $this->monitor(threshold: null);

        $this->record($monitor, 60_000);

        $this->assertFalse($monitor->fresh()->is_degraded);
    }

    /**
     * Down outranks slow, and the degradation is cleared silently — an
     * "improved" alert during an outage would be nonsense.
     */
    public function test_going_down_clears_degradation_without_announcing_it(): void
    {
        $monitor = $this->monitor(threshold: 1000);

        $this->record($monitor, 1500);
        $this->record($monitor, 1500, up: false);

        $monitor->refresh();

        $this->assertFalse($monitor->is_degraded);
        $this->assertSame(MonitorStatus::Down, $monitor->status());

        $events = $this->dispatchedEvents();

        $this->assertContains(AlertEvent::Down, $events);
        $this->assertNotContains(AlertEvent::Improved, $events);
    }

    public function test_it_announces_each_edge_once(): void
    {
        $monitor = $this->monitor(threshold: 1000);

        $this->record($monitor, 1500);
        $this->record($monitor, 1600);
        $this->record($monitor, 200);
        $this->record($monitor, 300);

        $events = $this->dispatchedEvents();

        $this->assertSame(1, collect($events)->filter(fn ($e) => $e === AlertEvent::Degraded)->count());
        $this->assertSame(1, collect($events)->filter(fn ($e) => $e === AlertEvent::Improved)->count());
    }

    public function test_the_status_filters_partition(): void
    {
        $slow = $this->monitor(threshold: 1000);
        $this->record($slow, 1500);

        $healthy = $this->monitor(threshold: 1000);
        $this->record($healthy, 100);

        $this->assertSame(
            [$slow->id],
            Monitor::query()->forUser($this->user)->whereStatus(MonitorStatus::Degraded)->pluck('id')->all(),
        );

        $this->assertSame(
            [$healthy->id],
            Monitor::query()->forUser($this->user)->whereStatus(MonitorStatus::Up)->pluck('id')->all(),
        );
    }

    public function test_the_summary_counters_still_sum_to_the_total(): void
    {
        $slow = $this->monitor(threshold: 1000);
        $this->record($slow, 1500);

        $healthy = $this->monitor(threshold: 1000);
        $this->record($healthy, 100);

        Monitor::factory()->forUser($this->user)->create(['is_active' => false]);

        $summary = app(UptimeStats::class)->summaryForUser($this->user, now()->subDay());

        $this->assertSame(1, $summary['degraded']);
        $this->assertSame(1, $summary['up']);
        $this->assertSame(
            $summary['total'],
            $summary['up'] + $summary['degraded'] + $summary['down']
                + $summary['paused'] + $summary['pending'],
        );
    }

    /**
     * @return array<int, AlertEvent>
     */
    private function dispatchedEvents(): array
    {
        return Queue::pushed(SendAlert::class)
            ->map(fn (SendAlert $job) => $job->message->event)
            ->values()
            ->all();
    }
}
