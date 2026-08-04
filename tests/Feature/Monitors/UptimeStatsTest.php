<?php

namespace Tests\Feature\Monitors;

use App\Models\Incident;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\User;
use App\Monitoring\UptimeStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UptimeStatsTest extends TestCase
{
    use RefreshDatabase;

    private UptimeStats $stats;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stats = app(UptimeStats::class);
        $this->user = User::factory()->create();
    }

    private function monitor(array $attributes = []): Monitor
    {
        return Monitor::factory()->forUser($this->user)->create($attributes);
    }

    public function test_uptime_is_the_share_of_successful_checks(): void
    {
        $monitor = $this->monitor();

        MonitorCheck::factory()->count(19)->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subMinutes(30),
            'response_ms' => 100,
        ]);
        MonitorCheck::factory()->down()->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subMinutes(30),
        ]);

        $result = $this->stats->forMonitor($monitor, now()->subDay());

        $this->assertSame(95.0, $result['uptime_percentage']);
        $this->assertSame(20, $result['total_checks']);
        $this->assertSame(1, $result['failed_checks']);
    }

    public function test_uptime_is_null_without_any_checks(): void
    {
        $result = $this->stats->forMonitor($this->monitor(), now()->subDay());

        $this->assertNull($result['uptime_percentage']);
        $this->assertNull($result['avg_response_ms']);
        $this->assertNull($result['p95_response_ms']);
    }

    public function test_checks_outside_the_window_are_ignored(): void
    {
        $monitor = $this->monitor();

        MonitorCheck::factory()->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subDays(5),
        ]);

        $this->assertSame(0, $this->stats->forMonitor($monitor, now()->subDay())['total_checks']);
    }

    public function test_average_response_only_counts_successful_checks(): void
    {
        $monitor = $this->monitor();

        foreach ([100, 200, 300] as $ms) {
            MonitorCheck::factory()->create([
                'monitor_id' => $monitor->id,
                'checked_at' => now()->subMinutes(10),
                'response_ms' => $ms,
            ]);
        }

        MonitorCheck::factory()->down()->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subMinutes(10),
            'response_ms' => 9000,
        ]);

        $this->assertSame(200, $this->stats->forMonitor($monitor, now()->subDay())['avg_response_ms']);
    }

    public function test_p95_reports_a_high_percentile(): void
    {
        $monitor = $this->monitor();

        for ($i = 1; $i <= 100; $i++) {
            MonitorCheck::factory()->create([
                'monitor_id' => $monitor->id,
                'checked_at' => now()->subMinutes(10),
                'response_ms' => $i,
            ]);
        }

        $p95 = $this->stats->forMonitor($monitor, now()->subDay())['p95_response_ms'];

        $this->assertGreaterThanOrEqual(90, $p95);
        $this->assertLessThanOrEqual(100, $p95);
    }

    public function test_downtime_counts_only_the_part_inside_the_window(): void
    {
        $monitor = $this->monitor();

        // Started well before the window and resolved 30 minutes into it.
        Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'started_at' => now()->subHours(5),
            'resolved_at' => now()->subMinutes(30),
        ]);

        $downtime = $this->stats->forMonitor($monitor, now()->subHour())['downtime_seconds'];

        $this->assertEqualsWithDelta(1800, $downtime, 5);
    }

    public function test_an_ongoing_incident_counts_up_to_now(): void
    {
        $monitor = $this->monitor();

        Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'started_at' => now()->subMinutes(10),
            'resolved_at' => null,
        ]);

        $this->assertEqualsWithDelta(
            600,
            $this->stats->forMonitor($monitor, now()->subDay())['downtime_seconds'],
            5,
        );
    }

    public function test_the_user_summary_counts_monitors_by_state(): void
    {
        Monitor::factory()->forUser($this->user)->up()->count(3)->create();
        Monitor::factory()->forUser($this->user)->down()->count(2)->create();
        Monitor::factory()->forUser($this->user)->paused()->create();
        Monitor::factory()->forUser($this->user)->create(['latest_is_up' => null]);

        // Another user's monitors must not leak into the totals.
        Monitor::factory()->forUser(User::factory()->create())->up()->count(4)->create();

        $summary = $this->stats->summaryForUser($this->user, now()->subDay());

        $this->assertSame(7, $summary['total']);
        $this->assertSame(3, $summary['up']);
        $this->assertSame(2, $summary['down']);
        $this->assertSame(1, $summary['paused']);
        $this->assertSame(1, $summary['pending']);
    }

    public function test_the_summary_counts_ongoing_incidents_only(): void
    {
        $monitor = $this->monitor();

        Incident::factory()->create(['monitor_id' => $monitor->id]);
        Incident::factory()->resolved()->create(['monitor_id' => $monitor->id]);

        $this->assertSame(
            1,
            $this->stats->summaryForUser($this->user, now()->subDay())['ongoing_incidents'],
        );
    }

    public function test_the_response_series_buckets_checks_over_time(): void
    {
        $monitor = $this->monitor();

        MonitorCheck::factory()->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subHours(20),
            'response_ms' => 100,
        ]);
        MonitorCheck::factory()->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subMinutes(5),
            'response_ms' => 300,
        ]);

        $series = $this->stats->responseSeries($monitor, now()->subDay());

        $this->assertCount(2, $series);
        $this->assertSame(100, $series[0]['avg_response_ms']);
        $this->assertSame(300, $series[1]['avg_response_ms']);
    }

    public function test_daily_uptime_rolls_checks_up_per_day(): void
    {
        $monitor = $this->monitor();

        MonitorCheck::factory()->count(3)->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subDay()->setTime(12, 0),
        ]);
        MonitorCheck::factory()->down()->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subDay()->setTime(13, 0),
        ]);

        $daily = $this->stats->dailyUptime($monitor, 7);

        $this->assertCount(1, $daily);
        $this->assertSame(75.0, $daily[0]['uptime_percentage']);
        $this->assertSame(4, $daily[0]['total']);
    }
}
