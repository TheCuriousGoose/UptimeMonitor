<?php

namespace Tests\Feature\Alerting;

use App\Jobs\SendAlert;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Monitoring\AlertMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Every notifier used to decide "is this bad news?" by comparing the event
 * against Down. That is only correct for two of the five events, so a
 * reminder about a still-open outage and a degradation both read as good
 * news — and on the incident-tracking services, actively closed a live alert.
 */
class AlertEventBranchingTest extends TestCase
{
    use RefreshDatabase;

    private function monitor(): Monitor
    {
        return Monitor::factory()
            ->forUser(User::factory()->create())
            ->create(['name' => 'Checkout API', 'url' => 'https://api.example.com']);
    }

    private function ongoingIncident(Monitor $monitor): Incident
    {
        return Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'started_at' => now()->subMinutes(20),
            'resolved_at' => null,
        ]);
    }

    public function test_a_reminder_is_not_styled_as_a_recovery_in_slack(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->slack()->create(['user_id' => $monitor->created_by]);

        SendAlert::dispatchSync($channel, AlertMessage::reminder($monitor, $this->ongoingIncident($monitor)));

        Http::assertSent(fn ($request) => $request['attachments'][0]['color'] === '#dc2626'
            && str_contains($request['text'], ':red_circle:'));
    }

    public function test_a_degradation_is_styled_as_a_warning_rather_than_a_recovery(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->slack()->create(['user_id' => $monitor->created_by]);

        SendAlert::dispatchSync($channel, AlertMessage::degraded($monitor, 4200, 2000));

        Http::assertSent(fn ($request) => $request['attachments'][0]['color'] === '#f59e0b'
            && str_contains($request['text'], ':large_yellow_circle:'));
    }

    public function test_discord_paints_a_reminder_red_not_green(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->discord()->create(['user_id' => $monitor->created_by]);

        SendAlert::dispatchSync($channel, AlertMessage::reminder($monitor, $this->ongoingIncident($monitor)));

        Http::assertSent(fn ($request) => $request['embeds'][0]['color'] === 0xDC2626);
    }

    public function test_google_chat_labels_a_reminder_as_still_down(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->googleChat()->create(['user_id' => $monitor->created_by]);

        SendAlert::dispatchSync($channel, AlertMessage::reminder($monitor, $this->ongoingIncident($monitor)));

        Http::assertSent(
            fn ($request) => $request['cardsV2'][0]['card']['sections'][0]['widgets'][1]['decoratedText']['text'] === 'Still down',
        );
    }

    public function test_a_reminder_does_not_close_the_opsgenie_alert_it_is_reminding_about(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->opsgenie()->create(['user_id' => $monitor->created_by]);

        SendAlert::dispatchSync($channel, AlertMessage::reminder($monitor, $this->ongoingIncident($monitor)));

        Http::assertSent(fn ($request) => ! str_contains($request->url(), '/close'));
    }

    public function test_a_degradation_does_not_close_the_opsgenie_alert(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->opsgenie()->create(['user_id' => $monitor->created_by]);

        SendAlert::dispatchSync($channel, AlertMessage::degraded($monitor, 4200, 2000));

        Http::assertSent(fn ($request) => ! str_contains($request->url(), '/close')
            && $request['priority'] === 'P3');
    }

    public function test_a_recovery_still_closes_the_opsgenie_alert(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->opsgenie()->create(['user_id' => $monitor->created_by]);

        SendAlert::dispatchSync($channel, AlertMessage::recovered($monitor));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/close'));
    }

    public function test_latency_alerts_use_a_separate_pagerduty_dedup_key_from_outages(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->pagerDuty()->create(['user_id' => $monitor->created_by]);

        SendAlert::dispatchSync($channel, AlertMessage::down($monitor, 'HTTP 503'));
        SendAlert::dispatchSync($channel, AlertMessage::degraded($monitor, 4200, 2000));

        Http::assertSent(fn ($request) => $request['dedup_key'] === "monitor-{$monitor->uuid}"
            && $request['event_action'] === 'trigger');

        Http::assertSent(fn ($request) => $request['dedup_key'] === "monitor-{$monitor->uuid}-latency"
            && $request['payload']['severity'] === 'warning');
    }

    public function test_an_improvement_resolves_only_the_latency_page(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->pagerDuty()->create(['user_id' => $monitor->created_by]);

        SendAlert::dispatchSync($channel, AlertMessage::improved($monitor, 900));

        Http::assertSent(fn ($request) => $request['event_action'] === 'resolve'
            && $request['dedup_key'] === "monitor-{$monitor->uuid}-latency");
    }

    public function test_a_channel_with_no_destination_delivers_nothing(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->slack()->create([
            'user_id' => $monitor->created_by,
            'config' => ['url' => ''],
        ]);

        SendAlert::dispatchSync($channel, AlertMessage::down($monitor, 'HTTP 503'));

        Http::assertNothingSent();
    }
}
