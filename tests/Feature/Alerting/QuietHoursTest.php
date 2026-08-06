<?php

namespace Tests\Feature\Alerting;

use App\Checkers\CheckResult;
use App\Jobs\SendAlert;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Monitoring\StatusEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QuietHoursTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        Queue::fake();

        $this->user = User::factory()->withRole('User')->create();
    }

    private function channel(array $attributes = []): NotificationChannel
    {
        return NotificationChannel::factory()->for($this->user, 'user')->create(array_merge([
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '07:00',
            'quiet_hours_timezone' => 'UTC',
        ], $attributes));
    }

    private function takeDown(): Monitor
    {
        $monitor = Monitor::factory()->forUser($this->user)->create([
            'confirmation_threshold' => 1,
            'latest_is_up' => true,
        ]);

        app(StatusEvaluator::class)->record($monitor, CheckResult::down('boom', 10));

        return $monitor->fresh();
    }

    public function test_a_wrap_around_window_is_evaluated_correctly(): void
    {
        $channel = $this->channel();

        $this->assertTrue($channel->isQuiet(now()->setTime(23, 30)));
        $this->assertTrue($channel->isQuiet(now()->setTime(6, 30)));
        $this->assertFalse($channel->isQuiet(now()->setTime(12, 0)));
        $this->assertFalse($channel->isQuiet(now()->setTime(7, 0)));
    }

    public function test_the_window_is_read_in_the_channels_own_timezone(): void
    {
        $channel = $this->channel(['quiet_hours_timezone' => 'Australia/Sydney']);

        // 03:00 UTC is 14:00 in Sydney — awake there, asleep by UTC.
        $this->assertFalse($channel->isQuiet(now()->setTime(3, 0)));
    }

    public function test_an_outage_inside_the_window_is_deferred_not_sent(): void
    {
        $this->travelTo(now()->setTime(23, 0));

        $channel = $this->channel();
        $this->takeDown();

        Queue::assertNotPushed(SendAlert::class);

        $this->assertDatabaseHas('incident_notifications', [
            'notification_channel_id' => $channel->id,
            'notify_count' => 0,
        ]);
    }

    public function test_the_sweep_delivers_it_once_the_window_closes(): void
    {
        $this->travelTo(now()->setTime(23, 0));

        $this->channel();
        $this->takeDown();

        $this->travelTo(now()->addHours(9));
        $this->artisan('monitors:sweep-alerts')->assertSuccessful();

        Queue::assertPushed(SendAlert::class);
    }

    /**
     * Sleeping through an outage that fixed itself is the whole point.
     */
    public function test_an_incident_resolved_during_the_window_delivers_nothing(): void
    {
        $this->travelTo(now()->setTime(23, 0));

        $this->channel();
        $monitor = $this->takeDown();

        app(StatusEvaluator::class)->record($monitor, CheckResult::up(10));

        $this->travelTo(now()->addHours(9));
        $this->artisan('monitors:sweep-alerts')->assertSuccessful();

        Queue::assertNotPushed(SendAlert::class);
    }

    public function test_outside_the_window_alerts_send_immediately(): void
    {
        $this->travelTo(now()->setTime(12, 0));

        $channel = $this->channel();
        $this->takeDown();

        Queue::assertPushed(SendAlert::class);

        $this->assertDatabaseHas('incident_notifications', [
            'notification_channel_id' => $channel->id,
            'notify_count' => 1,
        ]);
    }

    public function test_a_channel_with_no_window_is_never_quiet(): void
    {
        $channel = $this->channel([
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
        ]);

        $this->assertFalse($channel->isQuiet(now()->setTime(3, 0)));
    }
}
