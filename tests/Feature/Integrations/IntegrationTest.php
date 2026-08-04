<?php

namespace Tests\Feature\Integrations;

use App\Enums\ChannelType;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Monitoring\AlertMessage;
use App\Monitoring\Notifiers\NotifierRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Http::preventStrayRequests();
    }

    private function user(): User
    {
        return User::factory()->withRole('User')->create();
    }

    private function pagerDuty(User $user): NotificationChannel
    {
        return NotificationChannel::factory()->for($user, 'user')->create([
            'type' => ChannelType::PagerDuty,
            'config' => ['routing_key' => str_repeat('a', 32)],
        ]);
    }

    // -- Surface separation ------------------------------------------------

    public function test_integrations_and_channels_do_not_appear_on_each_others_pages(): void
    {
        $user = $this->user();
        $this->pagerDuty($user);
        NotificationChannel::factory()->for($user, 'user')->create([
            'type' => ChannelType::Email,
            'config' => ['email' => 'ops@example.test'],
        ]);

        $this->actingAs($user)
            ->get(route('integrations.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('integrations/Index')
                ->has('integrations', 1)
                ->where('integrations.0.type', 'pagerduty'));

        $this->actingAs($user)
            ->get(route('channels.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('channels', 1)
                ->where('channels.0.type', 'email'));
    }

    public function test_an_integration_type_cannot_be_created_through_the_channels_endpoint(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('channels.store'), [
                'name' => 'Sneaky',
                'type' => 'pagerduty',
                'config' => ['routing_key' => str_repeat('a', 32)],
            ])
            ->assertStatus(422);

        $this->assertSame(0, $user->notificationChannels()->count());
    }

    public function test_a_basic_type_cannot_be_created_through_the_integrations_endpoint(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('integrations.store'), [
                'name' => 'Sneaky',
                'type' => 'email',
                'config' => ['email' => 'ops@example.test'],
            ])
            ->assertStatus(422);

        $this->assertSame(0, $user->notificationChannels()->count());
    }

    // -- Credential handling ----------------------------------------------

    /**
     * The page payload is HTML the browser keeps. An API credential must
     * never be serialised into it.
     */
    public function test_credentials_are_masked_and_never_sent_to_the_browser(): void
    {
        $user = $this->user();
        $key = str_repeat('a', 28).'wxyz';
        NotificationChannel::factory()->for($user, 'user')->create([
            'type' => ChannelType::PagerDuty,
            'config' => ['routing_key' => $key],
        ]);

        $response = $this->actingAs($user)->get(route('integrations.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('integrations.0.destination', '••••••••wxyz'));

        $response->assertDontSee($key);
    }

    public function test_a_teams_webhook_url_is_not_masked(): void
    {
        $user = $this->user();
        NotificationChannel::factory()->for($user, 'user')->create([
            'type' => ChannelType::Teams,
            'config' => ['url' => 'https://outlook.office.com/webhook/abc'],
        ]);

        $this->actingAs($user)
            ->get(route('integrations.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('integrations.0.destination', 'https://outlook.office.com/webhook/abc'));
    }

    public function test_config_keys_belonging_to_another_type_are_stripped(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('integrations.store'), [
            'name' => 'Mixed payload',
            'type' => 'teams',
            'config' => [
                'url' => 'https://outlook.office.com/webhook/abc',
                'routing_key' => str_repeat('b', 32),
                'api_key' => 'leak',
            ],
        ])->assertRedirect();

        $this->assertSame(
            ['url' => 'https://outlook.office.com/webhook/abc'],
            $user->notificationChannels()->sole()->config,
        );
    }

    public function test_a_pagerduty_key_must_be_a_valid_integration_key(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('integrations.store'), [
                'name' => 'Bad key',
                'type' => 'pagerduty',
                'config' => ['routing_key' => 'too-short'],
            ])
            ->assertSessionHasErrors('config.routing_key');
    }

    // -- Delivery ----------------------------------------------------------

    /**
     * The whole point of PagerDuty over a plain webhook: the recovery has to
     * resolve the incident the outage opened, keyed by the same dedup_key.
     */
    public function test_pagerduty_triggers_and_resolves_with_a_stable_dedup_key(): void
    {
        Http::fake(['events.pagerduty.com/*' => Http::response([], 202)]);

        $user = $this->user();
        $channel = $this->pagerDuty($user);
        $monitor = Monitor::factory()->forUser($user)->create();

        $notifier = app(NotifierRegistry::class)->resolve(ChannelType::PagerDuty->value);

        $notifier->send($channel, AlertMessage::down($monitor, 'HTTP 503'));
        $notifier->send($channel, AlertMessage::recovered($monitor));

        $expectedKey = 'monitor-'.$monitor->uuid;

        Http::assertSent(fn (Request $r) => $r['event_action'] === 'trigger'
            && $r['dedup_key'] === $expectedKey
            && $r['payload']['summary'] === $monitor->name.' is DOWN');

        Http::assertSent(fn (Request $r) => $r['event_action'] === 'resolve'
            && $r['dedup_key'] === $expectedKey
            // PagerDuty rejects a resolve carrying a payload block.
            && ! isset($r['payload']));
    }

    public function test_opsgenie_opens_and_closes_by_alias(): void
    {
        Http::fake(['api.opsgenie.com/*' => Http::response([], 202)]);

        $user = $this->user();
        $channel = NotificationChannel::factory()->for($user, 'user')->create([
            'type' => ChannelType::Opsgenie,
            'config' => ['api_key' => 'genie-key'],
        ]);
        $monitor = Monitor::factory()->forUser($user)->create();

        $notifier = app(NotifierRegistry::class)->resolve(ChannelType::Opsgenie->value);

        $notifier->send($channel, AlertMessage::down($monitor, 'HTTP 503'));
        $notifier->send($channel, AlertMessage::recovered($monitor));

        $alias = 'monitor-'.$monitor->uuid;

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.opsgenie.com/v2/alerts'
            && $r->hasHeader('Authorization', 'GenieKey genie-key')
            && $r['alias'] === $alias);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), urlencode($alias).'/close'));
    }

    public function test_teams_posts_a_message_card(): void
    {
        Http::fake();

        $user = $this->user();
        $channel = NotificationChannel::factory()->for($user, 'user')->create([
            'type' => ChannelType::Teams,
            'config' => ['url' => 'https://outlook.office.com/webhook/abc'],
        ]);
        $monitor = Monitor::factory()->forUser($user)->create();

        app(NotifierRegistry::class)
            ->resolve(ChannelType::Teams->value)
            ->send($channel, AlertMessage::down($monitor, 'HTTP 503'));

        Http::assertSent(fn (Request $r) => $r['@type'] === 'MessageCard'
            && $r['themeColor'] === 'DC2626');
    }

    public function test_a_channel_with_no_credential_sends_nothing(): void
    {
        Http::fake();

        $user = $this->user();
        $channel = NotificationChannel::factory()->for($user, 'user')->create([
            'type' => ChannelType::PagerDuty,
            'config' => [],
        ]);
        $monitor = Monitor::factory()->forUser($user)->create();

        app(NotifierRegistry::class)
            ->resolve(ChannelType::PagerDuty->value)
            ->send($channel, AlertMessage::down($monitor, 'boom'));

        Http::assertNothingSent();
    }

    // -- Ownership ---------------------------------------------------------

    public function test_another_users_integration_cannot_be_disconnected(): void
    {
        $owner = $this->user();
        $attacker = $this->user();
        $integration = $this->pagerDuty($owner);

        $this->actingAs($attacker)
            ->delete(route('integrations.destroy', $integration))
            ->assertForbidden();

        $this->assertDatabaseHas('notification_channels', ['id' => $integration->id]);
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('integrations.index'))->assertRedirect(route('login'));
    }
}
