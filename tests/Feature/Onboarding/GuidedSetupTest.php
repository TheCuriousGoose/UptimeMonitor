<?php

namespace Tests\Feature\Onboarding;

use App\Enums\AlertScope;
use App\Enums\ChannelType;
use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Onboarding\OnboardingProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The guided setup commits a monitor and, optionally, somewhere for it to
 * reach the user — in one transaction, because a half-finished setup is the
 * outcome the whole flow exists to prevent.
 */
class GuidedSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Queue::fake();
    }

    private function user(): User
    {
        return User::factory()->withRole('User')->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Marketing site',
            'url' => 'https://example.com',
            'type' => 'http',
            'timeout' => 10,
            'interval_seconds' => 300,
            'confirmation_threshold' => 2,
            'recovery_threshold' => 1,
            'is_active' => true,
        ], $overrides);
    }

    public function test_a_brand_new_account_is_taken_to_the_guided_setup(): void
    {
        $this->actingAs($this->user())
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding.show'));
    }

    public function test_an_account_with_a_monitor_gets_the_dashboard(): void
    {
        $user = $this->user();
        Monitor::factory()->forUser($user)->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_skipping_is_remembered_and_stops_the_redirect(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('onboarding.skip'))
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(OnboardingProgress::for($user->fresh())['dismissed']);

        $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
    }

    public function test_it_creates_the_monitor_and_runs_a_first_check(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('onboarding.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $monitor = Monitor::query()->firstOrFail();

        $this->assertSame('Marketing site', $monitor->name);
        $this->assertSame(300, $monitor->interval_seconds);
        $this->assertSame(2, $monitor->confirmation_threshold);
        $this->assertSame($user->id, $monitor->created_by);

        Queue::assertPushed(RunMonitorCheck::class);
    }

    public function test_it_creates_an_email_channel_covering_every_monitor(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('onboarding.store'), $this->payload([
                'alert_email' => 'ops@example.com',
            ]))
            ->assertSessionHasNoErrors();

        $channel = NotificationChannel::query()->firstOrFail();

        $this->assertSame(ChannelType::Email, $channel->type);
        $this->assertSame('ops@example.com', $channel->config['email']);
        $this->assertSame(AlertScope::All, $channel->alert_scope);
        $this->assertTrue($channel->is_active);
    }

    public function test_running_it_twice_does_not_stack_up_duplicate_channels(): void
    {
        $user = $this->user();

        foreach (['First', 'Second'] as $name) {
            $this->actingAs($user)->post(route('onboarding.store'), $this->payload([
                'name' => $name,
                'alert_email' => 'ops@example.com',
            ]));
        }

        $this->assertSame(2, Monitor::query()->count());
        $this->assertSame(1, NotificationChannel::query()->count());
    }

    public function test_it_can_attach_an_integration_the_user_already_had(): void
    {
        $user = $this->user();
        $channel = NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'alert_scope' => AlertScope::Selected,
        ]);

        $this->actingAs($user)
            ->post(route('onboarding.store'), $this->payload([
                'notification_channels' => [$channel->uuid],
            ]))
            ->assertSessionHasNoErrors();

        $monitor = Monitor::query()->firstOrFail();

        $this->assertSame([$channel->id], $monitor->notificationChannels->pluck('id')->all());
    }

    public function test_it_will_not_attach_someone_elses_integration(): void
    {
        $theirs = NotificationChannel::factory()->create([
            'user_id' => $this->user()->id,
        ]);

        $this->actingAs($this->user())
            ->post(route('onboarding.store'), $this->payload([
                'notification_channels' => [$theirs->uuid],
            ]))
            // The channel exists but belongs to somebody else, so the same
            // ownership-scoped exists rule the monitor form uses rejects it.
            ->assertSessionHasErrors('notification_channels.0');

        $this->assertSame(0, Monitor::query()->count());
    }

    public function test_it_validates_the_monitor_the_same_way_the_form_does(): void
    {
        $this->actingAs($this->user())
            ->post(route('onboarding.store'), $this->payload(['url' => 'not a url']))
            ->assertSessionHasErrors('url');

        $this->assertSame(0, Monitor::query()->count());
    }

    public function test_a_bad_alert_email_is_rejected_before_anything_is_created(): void
    {
        $this->actingAs($this->user())
            ->post(route('onboarding.store'), $this->payload([
                'alert_email' => 'not-an-email',
            ]))
            ->assertSessionHasErrors('alert_email');

        $this->assertSame(0, Monitor::query()->count());
        $this->assertSame(0, NotificationChannel::query()->count());
    }

    public function test_the_setup_screen_offers_the_users_own_address(): void
    {
        $user = $this->user();

        $props = $this->actingAs($user)
            ->get(route('onboarding.show'))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame($user->email, $props['suggestedEmail']);
    }

    public function test_a_guest_cannot_reach_it(): void
    {
        $this->get(route('onboarding.show'))->assertRedirect(route('login'));
    }
}
