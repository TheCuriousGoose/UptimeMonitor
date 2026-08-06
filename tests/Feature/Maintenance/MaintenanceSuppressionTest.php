<?php

namespace Tests\Feature\Maintenance;

use App\Checkers\CheckResult;
use App\Enums\MonitorStatus;
use App\Jobs\SendAlert;
use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Monitoring\StatusEvaluator;
use App\Monitoring\UptimeStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MaintenanceSuppressionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        Queue::fake();

        $this->user = User::factory()->withRole('User')->create();
        NotificationChannel::factory()->for($this->user, 'user')->create();
    }

    private function monitorInWindow(): Monitor
    {
        $monitor = Monitor::factory()->forUser($this->user)->create([
            'confirmation_threshold' => 1,
            'latest_is_up' => true,
        ]);

        MaintenanceWindow::factory()
            ->for($this->user)
            ->create()
            ->monitors()
            ->attach($monitor);

        return $monitor;
    }

    private function takeDown(Monitor $monitor): void
    {
        app(StatusEvaluator::class)->record($monitor, CheckResult::down('boom', 10));
    }

    public function test_an_outage_inside_a_window_opens_a_flagged_incident_and_alerts_nobody(): void
    {
        $monitor = $this->monitorInWindow();

        $this->takeDown($monitor);

        $incident = $monitor->fresh()->ongoingIncident();

        $this->assertNotNull($incident, 'The incident is flagged, not skipped.');
        $this->assertTrue($incident->is_maintenance);

        Queue::assertNotPushed(SendAlert::class);
    }

    public function test_recovery_inside_a_window_announces_nothing(): void
    {
        $monitor = $this->monitorInWindow();

        $this->takeDown($monitor);
        app(StatusEvaluator::class)->record($monitor->fresh(), CheckResult::up(10));

        Queue::assertNotPushed(SendAlert::class);
    }

    public function test_an_outage_outliving_the_window_is_announced_by_the_sweep(): void
    {
        $monitor = $this->monitorInWindow();

        $this->takeDown($monitor);
        Queue::assertNotPushed(SendAlert::class);

        $this->travel(2)->hours();
        $this->artisan('monitors:sweep-alerts')->assertSuccessful();

        Queue::assertPushed(SendAlert::class);
        $this->assertFalse($monitor->fresh()->ongoingIncident()->is_maintenance);
    }

    public function test_it_announces_a_released_outage_only_once(): void
    {
        $monitor = $this->monitorInWindow();
        $this->takeDown($monitor);

        $this->travel(2)->hours();
        $this->artisan('monitors:sweep-alerts')->assertSuccessful();
        $this->artisan('monitors:sweep-alerts')->assertSuccessful();

        Queue::assertPushed(SendAlert::class, 1);
    }

    public function test_maintenance_incidents_do_not_count_as_downtime(): void
    {
        $monitor = $this->monitorInWindow();
        $this->takeDown($monitor);

        $stats = app(UptimeStats::class)->forMonitor($monitor->fresh(), now()->subDay());

        $this->assertSame(0, $stats['downtime_seconds']);
        $this->assertSame(0, $stats['incidents']);
    }

    public function test_degradation_is_frozen_during_a_window(): void
    {
        $monitor = $this->monitorInWindow();
        $monitor->update(['degraded_response_ms' => 100]);

        app(StatusEvaluator::class)->record($monitor->fresh(), CheckResult::up(5000));

        $this->assertFalse($monitor->fresh()->is_degraded);
        Queue::assertNotPushed(SendAlert::class);
    }

    public function test_the_sweep_marks_the_monitor_as_under_maintenance(): void
    {
        $monitor = $this->monitorInWindow();

        $this->artisan('monitors:sweep-alerts')->assertSuccessful();

        $monitor->refresh();

        $this->assertNotNull($monitor->maintenance_until);
        $this->assertSame(MonitorStatus::Maintenance, $monitor->status());
    }

    public function test_the_summary_counters_still_sum_to_the_total(): void
    {
        $this->monitorInWindow();
        Monitor::factory()->forUser($this->user)->up()->create();

        $this->artisan('monitors:sweep-alerts')->assertSuccessful();

        $summary = app(UptimeStats::class)->summaryForUser($this->user, now()->subDay());

        $this->assertSame(1, $summary['maintenance']);
        $this->assertSame(
            $summary['total'],
            $summary['up'] + $summary['degraded'] + $summary['down']
                + $summary['maintenance'] + $summary['paused'] + $summary['pending'],
        );
    }

    public function test_a_monitor_outside_any_window_alerts_normally(): void
    {
        $monitor = Monitor::factory()->forUser($this->user)->create([
            'confirmation_threshold' => 1,
            'latest_is_up' => true,
        ]);

        $this->takeDown($monitor);

        Queue::assertPushed(SendAlert::class);
        $this->assertFalse($monitor->fresh()->ongoingIncident()->is_maintenance);
    }
}
