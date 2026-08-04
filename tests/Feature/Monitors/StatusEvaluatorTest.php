<?php

namespace Tests\Feature\Monitors;

use App\Checkers\CheckResult;
use App\Jobs\SendAlert;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Monitoring\AlertEvent;
use App\Monitoring\StatusEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StatusEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    private function evaluator(): StatusEvaluator
    {
        return app(StatusEvaluator::class);
    }

    private function monitor(array $attributes = []): Monitor
    {
        $user = User::factory()->create();

        return Monitor::factory()->forUser($user)->create($attributes);
    }

    public function test_a_failed_check_marks_the_monitor_down_and_opens_an_incident(): void
    {
        $monitor = $this->monitor(['latest_is_up' => true]);

        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503', 120));

        $this->assertFalse($monitor->fresh()->latest_is_up);
        $this->assertSame(1, $monitor->fresh()->failure_streak);

        $incident = Incident::where('monitor_id', $monitor->id)->first();
        $this->assertNotNull($incident);
        $this->assertNull($incident->resolved_at);
        $this->assertSame('HTTP 503', $incident->cause);
    }

    public function test_the_check_row_records_the_result(): void
    {
        $monitor = $this->monitor();

        $check = $this->evaluator()->record($monitor, CheckResult::down('HTTP 503', 120, ['status_code' => 503]));

        $this->assertFalse($check->is_up);
        $this->assertSame(120, $check->response_ms);
        $this->assertSame('HTTP 503', $check->error);
        $this->assertSame(503, $check->meta['status_code']);
    }

    public function test_the_confirmation_threshold_delays_the_down_transition(): void
    {
        $monitor = $this->monitor(['latest_is_up' => true, 'confirmation_threshold' => 3]);

        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));
        $this->assertTrue($monitor->fresh()->latest_is_up, 'First failure should not flip the status.');
        $this->assertSame(0, Incident::count());

        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));
        $this->assertTrue($monitor->fresh()->latest_is_up, 'Second failure should not flip the status.');

        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));
        $this->assertFalse($monitor->fresh()->latest_is_up, 'Third failure should confirm the outage.');
        $this->assertSame(1, Incident::count());
    }

    public function test_a_success_resets_the_failure_streak_before_the_threshold(): void
    {
        $monitor = $this->monitor(['latest_is_up' => true, 'confirmation_threshold' => 3]);

        $this->evaluator()->record($monitor, CheckResult::down('blip'));
        $this->evaluator()->record($monitor, CheckResult::down('blip'));
        $this->evaluator()->record($monitor, CheckResult::up(80));

        $this->assertSame(0, $monitor->fresh()->failure_streak);
        $this->assertTrue($monitor->fresh()->latest_is_up);
        $this->assertSame(0, Incident::count());
    }

    public function test_recovery_resolves_the_open_incident(): void
    {
        $monitor = $this->monitor(['latest_is_up' => true]);

        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));
        $this->evaluator()->record($monitor, CheckResult::up(95));

        $incident = Incident::where('monitor_id', $monitor->id)->first();

        $this->assertTrue($monitor->fresh()->latest_is_up);
        $this->assertNotNull($incident->resolved_at);
    }

    public function test_continued_failure_does_not_open_a_second_incident(): void
    {
        $monitor = $this->monitor(['latest_is_up' => true]);

        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));
        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));
        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));

        $this->assertSame(1, Incident::count());
        $this->assertSame(3, Incident::first()->failed_checks);
    }

    public function test_a_later_outage_opens_a_new_incident(): void
    {
        $monitor = $this->monitor(['latest_is_up' => true]);

        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));
        $this->evaluator()->record($monitor, CheckResult::up(95));
        $this->evaluator()->record($monitor, CheckResult::down('HTTP 500'));

        $this->assertSame(2, Incident::count());
        $this->assertSame(1, Incident::ongoing()->count());
    }

    public function test_the_incident_is_backdated_to_the_first_failure_of_the_streak(): void
    {
        $monitor = $this->monitor(['latest_is_up' => true, 'confirmation_threshold' => 3]);

        $first = now()->subMinutes(10);

        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'), $first);
        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'), now()->subMinutes(5));
        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'), now());

        $this->assertSame(
            $first->format('Y-m-d H:i:s'),
            Incident::first()->started_at->format('Y-m-d H:i:s'),
        );
    }

    public function test_the_next_check_is_scheduled_from_the_check_time(): void
    {
        $monitor = $this->monitor(['interval_seconds' => 600]);
        $checkedAt = now();

        $this->evaluator()->record($monitor, CheckResult::up(50), $checkedAt);

        $this->assertSame(
            $checkedAt->addSeconds(600)->format('Y-m-d H:i'),
            $monitor->fresh()->next_check_at->format('Y-m-d H:i'),
        );
        $this->assertNotNull($monitor->fresh()->last_checked_at);
    }

    public function test_going_down_dispatches_an_alert_to_active_channels(): void
    {
        Queue::fake();

        $monitor = $this->monitor(['latest_is_up' => true]);
        $active = NotificationChannel::factory()->create(['user_id' => $monitor->created_by]);
        $inactive = NotificationChannel::factory()->inactive()->create(['user_id' => $monitor->created_by]);
        $monitor->notificationChannels()->attach([$active->id, $inactive->id]);

        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));

        Queue::assertPushed(SendAlert::class, 1);
        Queue::assertPushed(
            SendAlert::class,
            fn (SendAlert $job) => $job->channel->is($active)
                && $job->message->event === AlertEvent::Down,
        );
    }

    public function test_recovery_dispatches_a_recovered_alert(): void
    {
        $monitor = $this->monitor(['latest_is_up' => true]);
        $channel = NotificationChannel::factory()->create(['user_id' => $monitor->created_by]);
        $monitor->notificationChannels()->attach($channel);

        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));

        Queue::fake();
        $this->evaluator()->record($monitor, CheckResult::up(80));

        Queue::assertPushed(
            SendAlert::class,
            fn (SendAlert $job) => $job->message->event === AlertEvent::Recovered,
        );
    }

    public function test_a_monitor_that_stays_down_does_not_re_alert(): void
    {
        $monitor = $this->monitor(['latest_is_up' => true]);
        $channel = NotificationChannel::factory()->create(['user_id' => $monitor->created_by]);
        $monitor->notificationChannels()->attach($channel);

        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));

        Queue::fake();
        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));
        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));

        Queue::assertNothingPushed();
    }

    public function test_a_first_ever_successful_check_does_not_send_a_recovery_alert(): void
    {
        Queue::fake();

        $monitor = $this->monitor(['latest_is_up' => null]);
        $channel = NotificationChannel::factory()->create(['user_id' => $monitor->created_by]);
        $monitor->notificationChannels()->attach($channel);

        $this->evaluator()->record($monitor, CheckResult::up(70));

        Queue::assertNothingPushed();
        $this->assertTrue($monitor->fresh()->latest_is_up);
    }

    public function test_a_first_ever_failed_check_alerts_and_opens_an_incident(): void
    {
        Queue::fake();

        $monitor = $this->monitor(['latest_is_up' => null]);
        $channel = NotificationChannel::factory()->create(['user_id' => $monitor->created_by]);
        $monitor->notificationChannels()->attach($channel);

        $this->evaluator()->record($monitor, CheckResult::down('HTTP 503'));

        Queue::assertPushed(SendAlert::class, 1);
        $this->assertSame(1, Incident::count());
    }
}
