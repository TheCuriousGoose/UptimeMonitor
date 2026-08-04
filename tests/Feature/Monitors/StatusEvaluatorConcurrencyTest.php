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
 * The persist transaction is retried on deadlock. That is only safe because
 * it re-reads committed state before doing the streak arithmetic — otherwise
 * a retried attempt would increment a counter the rolled-back attempt had
 * already bumped.
 */
class StatusEvaluatorConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Queue::fake();
    }

    private function monitor(array $attributes = []): Monitor
    {
        $user = User::factory()->withRole('User')->create();

        return Monitor::factory()->forUser($user)->create($attributes);
    }

    public function test_streaks_are_computed_from_committed_state_not_a_stale_model(): void
    {
        $monitor = $this->monitor(['failure_streak' => 0, 'confirmation_threshold' => 5]);

        // Something else advanced the streak after this instance was loaded —
        // the same shape a retried transaction sees.
        Monitor::query()->whereKey($monitor->id)->update(['failure_streak' => 3]);

        app(StatusEvaluator::class)->record(
            $monitor,
            new CheckResult(isUp: false, responseMs: 0, error: 'timeout'),
        );

        // 3 committed + this failure = 4, not 1 from the stale in-memory zero.
        $this->assertSame(4, $monitor->fresh()->failure_streak);
    }

    public function test_a_success_resets_the_failure_streak_from_committed_state(): void
    {
        $monitor = $this->monitor(['failure_streak' => 0, 'success_streak' => 0]);

        Monitor::query()->whereKey($monitor->id)->update([
            'failure_streak' => 4,
            'success_streak' => 0,
        ]);

        app(StatusEvaluator::class)->record(
            $monitor,
            new CheckResult(isUp: true, responseMs: 120),
        );

        $fresh = $monitor->fresh();

        $this->assertSame(0, $fresh->failure_streak);
        $this->assertSame(1, $fresh->success_streak);
    }

    /**
     * The caller reads $monitor after record() returns (to dispatch alerts),
     * so the in-memory instance has to reflect what was written.
     */
    public function test_the_passed_model_reflects_the_persisted_state(): void
    {
        $monitor = $this->monitor(['confirmation_threshold' => 1]);

        app(StatusEvaluator::class)->record(
            $monitor,
            new CheckResult(isUp: false, responseMs: 0, error: 'boom'),
        );

        $this->assertFalse($monitor->latest_is_up);
        $this->assertSame(1, $monitor->failure_streak);
        $this->assertSame($monitor->fresh()->failure_streak, $monitor->failure_streak);
    }
}
