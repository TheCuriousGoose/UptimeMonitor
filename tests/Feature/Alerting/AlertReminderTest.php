<?php

namespace Tests\Feature\Alerting;

use App\Checkers\CheckResult;
use App\Jobs\SendAlert;
use App\Models\Incident;
use App\Models\IncidentNotification;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Monitoring\AlertEvent;
use App\Monitoring\StatusEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Alerts used to be strictly edge-triggered: an outage that started at 2am
 * and was still running at 9am had said nothing since 2am.
 */
class AlertReminderTest extends TestCase
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
        return NotificationChannel::factory()->for($this->user, 'user')->create($attributes);
    }

    private function downMonitor(): Monitor
    {
        $monitor = Monitor::factory()->forUser($this->user)->create([
            'confirmation_threshold' => 1,
            'latest_is_up' => true,
        ]);

        app(StatusEvaluator::class)->record($monitor, CheckResult::down('boom', 10));

        return $monitor->fresh();
    }

    private function sweep(): void
    {
        $this->artisan('monitors:sweep-alerts')->assertSuccessful();
    }

    private function events(): array
    {
        return Queue::pushed(SendAlert::class)
            ->map(fn (SendAlert $job) => $job->message->event)
            ->values()
            ->all();
    }

    public function test_the_outage_alert_writes_a_ledger_row(): void
    {
        $channel = $this->channel();
        $monitor = $this->downMonitor();

        $ledger = IncidentNotification::query()
            ->where('notification_channel_id', $channel->id)
            ->first();

        $this->assertNotNull($ledger);
        $this->assertSame(1, $ledger->notify_count);
        $this->assertTrue($monitor->ongoingIncident()->wasAnnounced());
    }

    public function test_a_reminder_fires_once_the_interval_has_passed(): void
    {
        $this->channel(['renotify_minutes' => 30]);
        $this->downMonitor();

        $this->sweep();
        $this->assertNotContains(AlertEvent::Reminder, $this->events());

        $this->travel(31)->minutes();
        $this->sweep();

        $this->assertContains(AlertEvent::Reminder, $this->events());
    }

    public function test_reminders_stop_at_the_limit(): void
    {
        $this->channel(['renotify_minutes' => 10, 'renotify_limit' => 2]);
        $this->downMonitor();

        for ($i = 0; $i < 5; $i++) {
            $this->travel(11)->minutes();
            $this->sweep();
        }

        $reminders = collect($this->events())->filter(fn ($e) => $e === AlertEvent::Reminder)->count();

        $this->assertSame(2, $reminders);
    }

    public function test_a_resolved_incident_stops_reminding(): void
    {
        $this->channel(['renotify_minutes' => 10]);
        $monitor = $this->downMonitor();

        app(StatusEvaluator::class)->record($monitor, CheckResult::up(10));

        $this->travel(60)->minutes();
        $this->sweep();

        $this->assertNotContains(AlertEvent::Reminder, $this->events());
    }

    public function test_a_channel_without_a_cadence_never_reminds(): void
    {
        $this->channel(['renotify_minutes' => null]);
        $this->downMonitor();

        $this->travel(1)->day();
        $this->sweep();

        $this->assertNotContains(AlertEvent::Reminder, $this->events());
    }

    public function test_two_channels_remind_on_their_own_cadences(): void
    {
        $fast = $this->channel(['renotify_minutes' => 5, 'name' => 'Fast']);
        $slow = $this->channel(['renotify_minutes' => 120, 'name' => 'Slow']);

        $this->downMonitor();

        $this->travel(6)->minutes();
        $this->sweep();

        $fastLedger = IncidentNotification::query()->where('notification_channel_id', $fast->id)->first();
        $slowLedger = IncidentNotification::query()->where('notification_channel_id', $slow->id)->first();

        $this->assertSame(2, $fastLedger->notify_count, 'The fast channel should have been reminded.');
        $this->assertSame(1, $slowLedger->notify_count, "The slow channel's cadence has not elapsed.");
    }

    /**
     * A suppressed outage must not start reminding about itself — the ledger
     * is what distinguishes "never announced" from "announced once".
     */
    public function test_an_unannounced_incident_never_reminds(): void
    {
        $channel = $this->channel(['renotify_minutes' => 10]);
        $monitor = Monitor::factory()->forUser($this->user)->create();

        Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'started_at' => now()->subHours(3),
            'resolved_at' => null,
        ]);

        $this->travel(30)->minutes();
        $this->sweep();

        $this->assertNotContains(AlertEvent::Reminder, $this->events());
        $this->assertDatabaseMissing('incident_notifications', [
            'notification_channel_id' => $channel->id,
        ]);
    }
}
