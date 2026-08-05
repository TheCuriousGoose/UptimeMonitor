<?php

namespace Tests\Feature\Alerting;

use App\Jobs\SendAlert;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function user(): User
    {
        return User::factory()->withRole('User')->create();
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('integrations.index'))->assertRedirect(route('login'));
    }

    public function test_a_user_only_sees_their_own_channels(): void
    {
        $user = $this->user();
        $mine = NotificationChannel::factory()->create(['user_id' => $user->id, 'name' => 'My email']);
        $theirs = NotificationChannel::factory()->create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Their email',
        ]);

        $this->actingAs($user)->get(route('integrations.index'))
            ->assertOk()
            ->assertSee($mine->name)
            ->assertDontSee($theirs->name);
    }

    public function test_a_user_can_create_an_email_channel(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('integrations.store'), [
            'name' => 'Ops email',
            'type' => 'email',
            'config' => ['email' => 'ops@example.com'],
        ])->assertRedirect(route('integrations.index'));

        $this->assertSame('ops@example.com', NotificationChannel::first()->destination());
    }

    public function test_an_email_channel_requires_a_valid_address(): void
    {
        $this->actingAs($this->user())->post(route('integrations.store'), [
            'name' => 'Ops email',
            'type' => 'email',
            'config' => ['email' => 'not-an-email'],
        ])->assertSessionHasErrors('config.email');
    }

    public function test_a_webhook_channel_requires_a_valid_url(): void
    {
        $this->actingAs($this->user())->post(route('integrations.store'), [
            'name' => 'Hook',
            'type' => 'webhook',
            'config' => ['url' => 'nope'],
        ])->assertSessionHasErrors('config.url');
    }

    public function test_config_keys_for_another_channel_type_are_discarded(): void
    {
        $this->actingAs($this->user())->post(route('integrations.store'), [
            'name' => 'Hook',
            'type' => 'webhook',
            'config' => ['url' => 'https://hooks.example.com', 'email' => 'leak@example.com'],
        ])->assertSessionHasNoErrors();

        $this->assertArrayNotHasKey('email', NotificationChannel::first()->config);
    }

    public function test_a_user_can_update_their_channel(): void
    {
        $user = $this->user();
        $channel = NotificationChannel::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put(route('integrations.update', $channel), [
            'name' => 'Renamed',
            'type' => 'email',
            'config' => ['email' => 'new@example.com'],
        ])->assertRedirect(route('integrations.index'));

        $this->assertSame('Renamed', $channel->fresh()->name);
        $this->assertSame('new@example.com', $channel->fresh()->destination());
    }

    public function test_a_user_cannot_update_someone_elses_channel(): void
    {
        $channel = NotificationChannel::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->user())->put(route('integrations.update', $channel), [
            'name' => 'Hijacked',
            'type' => 'email',
            'config' => ['email' => 'attacker@example.com'],
        ])->assertForbidden();
    }

    public function test_a_user_can_delete_their_channel(): void
    {
        $user = $this->user();
        $channel = NotificationChannel::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete(route('integrations.destroy', $channel))
            ->assertRedirect(route('integrations.index'));

        $this->assertSame(0, NotificationChannel::count());
    }

    public function test_a_user_cannot_delete_someone_elses_channel(): void
    {
        $channel = NotificationChannel::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->user())->delete(route('integrations.destroy', $channel))->assertForbidden();
        $this->assertSame(1, NotificationChannel::count());
    }

    public function test_a_user_can_send_a_test_alert(): void
    {
        Queue::fake();

        $user = $this->user();
        $channel = NotificationChannel::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('integrations.test', $channel))->assertRedirect();

        Queue::assertPushed(SendAlert::class, fn ($job) => $job->channel->is($channel));
    }

    public function test_a_user_cannot_test_someone_elses_channel(): void
    {
        Queue::fake();

        $channel = NotificationChannel::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->user())->post(route('integrations.test', $channel))->assertForbidden();

        Queue::assertNothingPushed();
    }
}
