<?php

namespace Tests\Feature\Integrations;

use App\Enums\AlertScope;
use App\Enums\ChannelType;
use App\Jobs\SendAlert;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Monitoring\AlertDispatcher;
use App\Monitoring\AlertMessage;
use App\Monitoring\AlertTemplate;
use App\Monitoring\Notifiers\NotifierRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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

    /**
     * Delivers through the same two steps SendAlert takes — render the
     * channel's wording, then hand it to the notifier — so these tests cannot
     * drift from what production actually sends.
     */
    private function deliver(NotificationChannel $channel, AlertMessage $message): void
    {
        $text = app(AlertTemplate::class)->render($message, $channel->templates);

        app(NotifierRegistry::class)
            ->resolve($channel->type->value)
            ->send($channel, $message, $text);
    }

    // -- One surface -------------------------------------------------------

    /**
     * Email and PagerDuty were once split across two pages on the theory that
     * they were different kinds of thing. They are not, and this asserts the
     * split has not crept back.
     */
    public function test_every_type_is_listed_on_the_one_integrations_page(): void
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
                ->has('integrations', 2)
                ->has('providers', count(ChannelType::cases())));
    }

    public function test_a_basic_type_can_be_created_through_the_integrations_endpoint(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('integrations.store'), [
                'name' => 'Ops email',
                'type' => 'email',
                'config' => ['email' => 'ops@example.test'],
            ])
            ->assertRedirect(route('integrations.index'));

        $this->assertSame('ops@example.test', $user->notificationChannels()->sole()->destination());
    }

    /**
     * This path 403'd for every user while the update request still looked for
     * a route parameter the integrations routes do not define.
     */
    public function test_a_user_can_update_their_integration(): void
    {
        $user = $this->user();
        $integration = $this->pagerDuty($user);

        $this->actingAs($user)
            ->put(route('integrations.update', $integration), [
                'name' => 'Renamed',
                'type' => 'pagerduty',
                'config' => ['routing_key' => str_repeat('c', 32)],
            ])
            ->assertRedirect(route('integrations.index'));

        $this->assertSame('Renamed', $integration->fresh()->name);
    }

    public function test_a_user_cannot_update_someone_elses_integration(): void
    {
        $integration = $this->pagerDuty($this->user());
        $original = $integration->name;

        $this->actingAs($this->user())
            ->put(route('integrations.update', $integration), [
                'name' => 'Hijacked',
                'type' => 'pagerduty',
                'config' => ['routing_key' => str_repeat('d', 32)],
            ])
            ->assertForbidden();

        $this->assertSame($original, $integration->fresh()->name);
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

        $this->deliver($channel, AlertMessage::down($monitor, 'HTTP 503'));
        $this->deliver($channel, AlertMessage::recovered($monitor));

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

        $this->deliver($channel, AlertMessage::down($monitor, 'HTTP 503'));
        $this->deliver($channel, AlertMessage::recovered($monitor));

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

        $this->deliver($channel, AlertMessage::down($monitor, 'HTTP 503'));

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

        $this->deliver($channel, AlertMessage::down($monitor, 'boom'));

        Http::assertNothingSent();
    }

    // -- Alert scope -------------------------------------------------------

    /**
     * The reason `all` is the default: a monitor created after the integration
     * still has to be covered, without anyone remembering to attach it.
     */
    public function test_an_all_scope_integration_alerts_a_monitor_it_is_not_attached_to(): void
    {
        Queue::fake();

        $user = $this->user();
        $channel = NotificationChannel::factory()->for($user, 'user')->create([
            'type' => ChannelType::Email,
            'config' => ['email' => 'ops@example.test'],
            'alert_scope' => AlertScope::All,
        ]);
        $monitor = Monitor::factory()->forUser($user)->create();

        $this->assertSame(0, $channel->monitors()->count());

        app(AlertDispatcher::class)->dispatch($monitor, AlertMessage::down($monitor, 'boom'));

        Queue::assertPushed(SendAlert::class, fn ($job) => $job->channel->is($channel));
    }

    public function test_a_selected_scope_integration_alerts_only_its_attached_monitors(): void
    {
        Queue::fake();

        $user = $this->user();
        $channel = NotificationChannel::factory()->for($user, 'user')->create([
            'type' => ChannelType::Email,
            'config' => ['email' => 'ops@example.test'],
            'alert_scope' => AlertScope::Selected,
        ]);
        $attached = Monitor::factory()->forUser($user)->create();
        $other = Monitor::factory()->forUser($user)->create();
        $channel->monitors()->attach($attached);

        app(AlertDispatcher::class)->dispatch($other, AlertMessage::down($other, 'boom'));

        Queue::assertNothingPushed();

        app(AlertDispatcher::class)->dispatch($attached, AlertMessage::down($attached, 'boom'));

        Queue::assertPushed(SendAlert::class, fn ($job) => $job->channel->is($channel));
    }

    public function test_another_users_monitors_never_reach_an_integration(): void
    {
        Queue::fake();

        $owner = $this->user();
        NotificationChannel::factory()->for($owner, 'user')->create([
            'type' => ChannelType::Email,
            'config' => ['email' => 'ops@example.test'],
            'alert_scope' => AlertScope::All,
        ]);

        $stranger = Monitor::factory()->forUser($this->user())->create();

        app(AlertDispatcher::class)->dispatch($stranger, AlertMessage::down($stranger, 'boom'));

        Queue::assertNothingPushed();
    }

    public function test_switching_to_all_clears_a_stale_monitor_selection(): void
    {
        $user = $this->user();
        $channel = NotificationChannel::factory()->for($user, 'user')->create([
            'type' => ChannelType::Email,
            'config' => ['email' => 'ops@example.test'],
            'alert_scope' => AlertScope::Selected,
        ]);
        $monitor = Monitor::factory()->forUser($user)->create();
        $channel->monitors()->attach($monitor);

        $this->actingAs($user)->put(route('integrations.update', $channel), [
            'name' => $channel->name,
            'type' => 'email',
            'config' => ['email' => 'ops@example.test'],
            'alert_scope' => 'all',
        ])->assertRedirect();

        $this->assertSame(0, $channel->monitors()->count());
    }

    public function test_monitors_belonging_to_someone_else_cannot_be_attached(): void
    {
        $user = $this->user();
        $theirs = Monitor::factory()->forUser($this->user())->create();

        $this->actingAs($user)->post(route('integrations.store'), [
            'name' => 'Mine',
            'type' => 'email',
            'config' => ['email' => 'ops@example.test'],
            'alert_scope' => 'selected',
            'monitors' => [$theirs->uuid],
        ])->assertRedirect();

        $this->assertSame(0, $user->notificationChannels()->sole()->monitors()->count());
    }

    // -- Message templates -------------------------------------------------

    public function test_a_custom_template_replaces_the_default_wording(): void
    {
        Http::fake();

        $user = $this->user();
        $channel = NotificationChannel::factory()->for($user, 'user')->create([
            'type' => ChannelType::Slack,
            'config' => ['url' => 'https://hooks.slack.com/services/abc'],
            'templates' => [
                'down' => [
                    'title' => '{{monitor.name}} broke',
                    'body' => '{{monitor.url}} said {{error}}',
                ],
            ],
        ]);
        $monitor = Monitor::factory()->forUser($user)->create(['name' => 'Checkout']);

        $this->deliver($channel, AlertMessage::down($monitor, 'HTTP 503'));

        Http::assertSent(fn (Request $r) => str_contains($r['text'], 'Checkout broke')
            && $r['attachments'][0]['text'] === "{$monitor->url} said HTTP 503");
    }

    /**
     * A template for one event must not bleed into the other.
     */
    public function test_an_event_without_a_template_keeps_the_default_wording(): void
    {
        Http::fake();

        $user = $this->user();
        $channel = NotificationChannel::factory()->for($user, 'user')->create([
            'type' => ChannelType::Slack,
            'config' => ['url' => 'https://hooks.slack.com/services/abc'],
            'templates' => ['down' => ['title' => 'custom down']],
        ]);
        $monitor = Monitor::factory()->forUser($user)->create(['name' => 'Checkout']);

        $this->deliver($channel, AlertMessage::recovered($monitor));

        Http::assertSent(fn (Request $r) => str_contains($r['text'], 'Checkout is back UP'));
    }

    public function test_a_null_template_reproduces_the_built_in_wording(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();
        $message = AlertMessage::down($monitor, 'HTTP 503');

        $text = app(AlertTemplate::class)->render($message, null);

        $this->assertSame($message->title(), $text->title);
        $this->assertSame($message->body(), $text->body);
    }

    public function test_an_unknown_placeholder_is_rejected_on_save(): void
    {
        $this->actingAs($this->user())->post(route('integrations.store'), [
            'name' => 'Typo',
            'type' => 'email',
            'config' => ['email' => 'ops@example.test'],
            'templates' => ['down' => ['title' => 'hi {{monitor.nmae}}']],
        ])->assertSessionHasErrors('templates.down.title');
    }

    /**
     * Templates are user input rendered server-side. Blade in one must stay
     * inert text, never something the renderer compiles or evaluates.
     */
    public function test_blade_syntax_in_a_template_is_never_executed(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        $text = app(AlertTemplate::class)->render(
            AlertMessage::down($monitor, 'boom'),
            ['down' => ['title' => '{{ $x }}', 'body' => '@php echo 1; @endphp']],
        );

        $this->assertSame('{{ $x }}', $text->title);
        $this->assertSame('@php echo 1; @endphp', $text->body);
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
