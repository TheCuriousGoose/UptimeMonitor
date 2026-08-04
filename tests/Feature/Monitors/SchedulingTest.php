<?php

namespace Tests\Feature\Monitors;

use App\Checkers\CheckerRegistry;
use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\User;
use App\Monitoring\StatusEvaluator;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    private function monitor(array $attributes = []): Monitor
    {
        return Monitor::factory()->forUser(User::factory()->create())->create($attributes);
    }

    public function test_a_new_monitor_is_due_immediately(): void
    {
        $monitor = $this->monitor();

        $this->assertNotNull($monitor->next_check_at);
        $this->assertTrue($monitor->next_check_at->lessThanOrEqualTo(now()->addSecond()));
    }

    public function test_only_due_active_monitors_are_dispatched(): void
    {
        Queue::fake();

        $due = $this->monitor(['next_check_at' => now()->subMinute()]);
        $this->monitor(['next_check_at' => now()->addHour()]);
        $this->monitor(['next_check_at' => now()->subMinute(), 'is_active' => false]);

        $this->artisan('monitors:dispatch')->assertSuccessful();

        Queue::assertPushed(RunMonitorCheck::class, 1);
        Queue::assertPushed(RunMonitorCheck::class, fn ($job) => $job->monitor->is($due));
    }

    /**
     * Without this, a backed up queue would re-dispatch the same monitor on
     * every scheduler tick.
     */
    public function test_dispatching_pushes_the_next_check_forward_immediately(): void
    {
        Queue::fake();

        $monitor = $this->monitor([
            'next_check_at' => now()->subMinute(),
            'interval_seconds' => 600,
        ]);

        $this->artisan('monitors:dispatch');

        $this->assertTrue($monitor->fresh()->next_check_at->greaterThan(now()->addMinutes(9)));
    }

    public function test_a_second_dispatch_does_not_re_queue_the_same_monitor(): void
    {
        Queue::fake();

        $this->monitor(['next_check_at' => now()->subMinute(), 'interval_seconds' => 300]);

        $this->artisan('monitors:dispatch');
        $this->artisan('monitors:dispatch');

        Queue::assertPushed(RunMonitorCheck::class, 1);
    }

    public function test_running_the_job_records_a_check(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $monitor = $this->monitor(['url' => 'https://example.com']);

        (new RunMonitorCheck($monitor))->handle(
            app(CheckerRegistry::class),
            app(StatusEvaluator::class),
        );

        $this->assertSame(1, MonitorCheck::where('monitor_id', $monitor->id)->count());
        $this->assertTrue($monitor->fresh()->latest_is_up);
    }

    public function test_pruning_removes_checks_older_than_the_retention_window(): void
    {
        $monitor = $this->monitor();

        MonitorCheck::factory()->create(['monitor_id' => $monitor->id, 'checked_at' => now()->subDays(100)]);
        MonitorCheck::factory()->create(['monitor_id' => $monitor->id, 'checked_at' => now()->subDays(10)]);

        $this->artisan('monitors:prune', ['--days' => 90])->assertSuccessful();

        $this->assertSame(1, MonitorCheck::count());
    }

    public function test_pruning_rejects_a_zero_day_window(): void
    {
        $this->artisan('monitors:prune', ['--days' => 0])->assertFailed();
    }

    public function test_the_scheduler_registers_the_monitoring_commands(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->command)
            ->implode(' ');

        $this->assertStringContainsString('monitors:dispatch', $events);
        $this->assertStringContainsString('monitors:prune', $events);
    }
}
