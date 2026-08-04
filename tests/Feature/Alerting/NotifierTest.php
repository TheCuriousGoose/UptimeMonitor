<?php

namespace Tests\Feature\Alerting;

use App\Jobs\SendAlert;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Monitoring\AlertMessage;
use App\Notifications\MonitorAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifierTest extends TestCase
{
    use RefreshDatabase;

    private function monitor(): Monitor
    {
        return Monitor::factory()
            ->forUser(User::factory()->create())
            ->create(['name' => 'Checkout API', 'url' => 'https://api.example.com']);
    }

    public function test_the_email_notifier_sends_to_the_configured_address(): void
    {
        Notification::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->create([
            'user_id' => $monitor->created_by,
            'config' => ['email' => 'ops@example.com'],
        ]);

        SendAlert::dispatchSync($channel, AlertMessage::down($monitor, 'HTTP 503'));

        Notification::assertSentOnDemand(
            MonitorAlertNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'ops@example.com',
        );
    }

    public function test_the_webhook_notifier_posts_a_json_payload(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->webhook('https://hooks.example.com/uptime')->create([
            'user_id' => $monitor->created_by,
        ]);

        SendAlert::dispatchSync($channel, AlertMessage::down($monitor, 'HTTP 503'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.example.com/uptime'
                && $request['event'] === 'down'
                && $request['error'] === 'HTTP 503'
                && $request['monitor']['name'] === 'Checkout API'
                && $request['title'] === 'Checkout API is DOWN';
        });
    }

    public function test_the_slack_notifier_uses_slack_message_formatting(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->slack('https://hooks.slack.com/services/a/b/c')->create([
            'user_id' => $monitor->created_by,
        ]);

        SendAlert::dispatchSync($channel, AlertMessage::down($monitor, 'HTTP 503'));

        Http::assertSent(function ($request) {
            return str_contains($request['text'], 'Checkout API is DOWN')
                && $request['attachments'][0]['color'] === '#dc2626';
        });
    }

    public function test_the_discord_notifier_sends_an_embed(): void
    {
        Http::fake();

        $monitor = $this->monitor();
        $channel = NotificationChannel::factory()->discord()->create([
            'user_id' => $monitor->created_by,
        ]);

        SendAlert::dispatchSync($channel, AlertMessage::recovered($monitor));

        Http::assertSent(function ($request) {
            return $request['embeds'][0]['title'] === 'Checkout API is back UP'
                && $request['embeds'][0]['color'] === 0x16A34A;
        });
    }

    public function test_a_recovery_message_reports_the_downtime(): void
    {
        $monitor = $this->monitor();
        $incident = Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'started_at' => now()->subMinutes(45),
            'resolved_at' => now(),
        ]);

        $message = AlertMessage::recovered($monitor, $incident);

        $this->assertStringContainsString('after 45m', $message->body());
        $this->assertSame('Checkout API is back UP', $message->title());
    }

    public function test_a_down_message_includes_the_error(): void
    {
        $message = AlertMessage::down($this->monitor(), 'Connection timed out');

        $this->assertStringContainsString('Connection timed out', $message->body());
        $this->assertStringContainsString('https://api.example.com', $message->body());
    }
}
