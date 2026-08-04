<?php

namespace Tests\Feature\Monitors;

use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MonitorActionsTest extends TestCase
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

    public function test_a_user_can_pause_a_monitor(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create(['is_active' => true]);

        $this->actingAs($user)
            ->patch(route('monitors.state', $monitor))
            ->assertRedirect();

        $this->assertFalse($monitor->fresh()->is_active);
    }

    public function test_resuming_a_monitor_makes_it_due_immediately(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->paused()->create([
            'next_check_at' => now()->addHour(),
            'failure_streak' => 4,
        ]);

        $this->actingAs($user)->patch(route('monitors.state', $monitor));

        $fresh = $monitor->fresh();

        $this->assertTrue($fresh->is_active);
        $this->assertSame(0, $fresh->failure_streak);
        $this->assertTrue($fresh->next_check_at->lessThanOrEqualTo(now()->addSecond()));
    }

    public function test_a_paused_monitor_is_not_dispatched(): void
    {
        Queue::fake();

        $user = $this->user();
        Monitor::factory()->forUser($user)->paused()->create(['next_check_at' => now()->subMinute()]);

        $this->artisan('monitors:dispatch')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_a_user_cannot_pause_someone_elses_monitor(): void
    {
        $monitor = Monitor::factory()->forUser($this->user())->create();

        $this->actingAs($this->user())
            ->patch(route('monitors.state', $monitor))
            ->assertForbidden();
    }

    public function test_a_user_can_run_a_check_on_demand(): void
    {
        Queue::fake();

        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->post(route('monitors.check', $monitor))
            ->assertRedirect();

        Queue::assertPushed(RunMonitorCheck::class, fn ($job) => $job->monitor->is($monitor));
    }

    public function test_a_user_cannot_run_a_check_on_someone_elses_monitor(): void
    {
        Queue::fake();

        $monitor = Monitor::factory()->forUser($this->user())->create();

        $this->actingAs($this->user())
            ->post(route('monitors.check', $monitor))
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_check_jobs_land_on_the_shared_default_queue(): void
    {
        Queue::fake();
        config(['monitoring.separate_queues' => false, 'monitoring.queue' => 'default']);

        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        $this->actingAs($user)->post(route('monitors.check', $monitor));

        Queue::assertPushed(RunMonitorCheck::class, fn ($job) => $job->queue === 'default');
    }
}
