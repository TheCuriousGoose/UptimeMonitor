<?php

namespace Tests\Feature\Monitors;

use App\Checkers\CheckResult;
use App\Models\Monitor;
use App\Models\User;
use App\Monitoring\StatusEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The mirror of the confirmation threshold. Before this existed, success_streak
 * was counted and never read, so one good answer from a flapping target flipped
 * the monitor up and the next failure paged everyone again.
 */
class RecoveryThresholdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        Queue::fake();
    }

    private function evaluator(): StatusEvaluator
    {
        return app(StatusEvaluator::class);
    }

    private function monitor(int $recovery): Monitor
    {
        return Monitor::factory()
            ->forUser(User::factory()->withRole('User')->create())
            ->create([
                'confirmation_threshold' => 1,
                'recovery_threshold' => $recovery,
                'latest_is_up' => true,
            ]);
    }

    private function recordFailure(Monitor $monitor): void
    {
        $this->evaluator()->record($monitor, CheckResult::down('boom', 100));
    }

    private function recordSuccess(Monitor $monitor): void
    {
        $this->evaluator()->record($monitor, CheckResult::up(100));
    }

    public function test_a_single_success_does_not_resolve_when_two_are_required(): void
    {
        $monitor = $this->monitor(recovery: 2);

        $this->recordFailure($monitor);
        $this->assertFalse($monitor->fresh()->latest_is_up);

        $this->recordSuccess($monitor);

        $monitor->refresh();
        $this->assertFalse($monitor->latest_is_up, 'One success must not clear a confirmed outage.');
        $this->assertNotNull($monitor->ongoingIncident());
    }

    public function test_the_confirming_success_resolves_the_incident(): void
    {
        $monitor = $this->monitor(recovery: 2);

        $this->recordFailure($monitor);
        $this->recordSuccess($monitor);
        $this->recordSuccess($monitor);

        $monitor->refresh();
        $this->assertTrue($monitor->latest_is_up);
        $this->assertNull($monitor->ongoingIncident());
    }

    public function test_a_failure_midway_restarts_the_recovery_count(): void
    {
        $monitor = $this->monitor(recovery: 3);

        $this->recordFailure($monitor);
        $this->recordSuccess($monitor);
        $this->recordSuccess($monitor);
        $this->recordFailure($monitor);
        $this->recordSuccess($monitor);
        $this->recordSuccess($monitor);

        $monitor->refresh();
        $this->assertFalse($monitor->latest_is_up, 'The streak must restart after the failure.');

        $this->recordSuccess($monitor);
        $this->assertTrue($monitor->fresh()->latest_is_up);
    }

    /**
     * The whole point of holding the status: a monitor answering successfully
     * while still held down must not inflate the incident's failure count.
     */
    public function test_a_held_recovery_does_not_inflate_the_failed_check_count(): void
    {
        $monitor = $this->monitor(recovery: 3);

        $this->recordFailure($monitor);
        $failedChecks = $monitor->ongoingIncident()->failed_checks;

        $this->recordSuccess($monitor);

        $this->assertSame($failedChecks, $monitor->fresh()->ongoingIncident()->failed_checks);
    }

    public function test_a_threshold_of_one_behaves_exactly_as_before(): void
    {
        $monitor = $this->monitor(recovery: 1);

        $this->recordFailure($monitor);
        $this->recordSuccess($monitor);

        $monitor->refresh();
        $this->assertTrue($monitor->latest_is_up);
        $this->assertNull($monitor->ongoingIncident());
    }

    /**
     * A brand new monitor has no outage to confirm a recovery from, so its
     * first success must leave Pending immediately whatever the threshold is.
     */
    public function test_a_pending_monitor_goes_up_on_its_first_success(): void
    {
        $monitor = $this->monitor(recovery: 3);
        $monitor->update(['latest_is_up' => null]);

        $this->recordSuccess($monitor);

        $this->assertTrue($monitor->fresh()->latest_is_up);
    }

    public function test_the_column_defaults_to_the_previous_behaviour(): void
    {
        $monitor = Monitor::factory()
            ->forUser(User::factory()->withRole('User')->create())
            ->create();

        $this->assertSame(1, $monitor->fresh()->recovery_threshold);
    }
}
