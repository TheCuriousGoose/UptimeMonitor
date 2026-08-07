<?php

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        // The limiter is backed by the cache, which persists across requests
        // within a test; start every case from a clean budget.
        RateLimiter::clear('check-now');
        RateLimiter::clear('channel-test');
    }

    private function user(): User
    {
        return User::factory()->withRole('User')->create();
    }

    public function test_check_now_is_throttled(): void
    {
        Queue::fake();

        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        // Six are allowed per minute; the seventh must be refused.
        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)
                ->post(route('monitors.check', $monitor))
                ->assertRedirect();
        }

        $response = $this->actingAs($user)->post(route('monitors.check', $monitor));

        $response->assertStatus(429);
        $this->assertNotNull($response->headers->get('Retry-After'));
    }

    public function test_channel_test_is_throttled_harder_than_check_now(): void
    {
        Queue::fake();

        $user = $this->user();
        $channel = NotificationChannel::factory()->for($user, 'user')->create();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)
                ->post(route('integrations.test', $channel))
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('integrations.test', $channel))
            ->assertStatus(429);
    }

    public function test_the_limit_is_per_user_not_per_ip(): void
    {
        Queue::fake();

        $first = $this->user();
        $second = $this->user();
        $monitor = Monitor::factory()->forUser($first)->create();
        $otherMonitor = Monitor::factory()->forUser($second)->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($first)->post(route('monitors.check', $monitor));
        }

        $this->actingAs($first)
            ->post(route('monitors.check', $monitor))
            ->assertStatus(429);

        // A second tenant sharing the same egress IP keeps a full budget.
        $this->actingAs($second)
            ->post(route('integrations.test', NotificationChannel::factory()->for($second, 'user')->create()))
            ->assertRedirect();

        $this->actingAs($second)
            ->post(route('monitors.check', $otherMonitor))
            ->assertRedirect();
    }

    public function test_reads_are_not_consumed_by_the_mutation_limiter(): void
    {
        $user = $this->user();

        // An account with nothing in it is sent to the guided setup instead,
        // and this needs a page that renders.
        Monitor::factory()->forUser($user)->create();

        // Well past the 60/min mutation ceiling, but these are GETs.
        for ($i = 0; $i < 70; $i++) {
            $this->actingAs($user)->get(route('dashboard'))->assertOk();
        }
    }

    public function test_json_clients_receive_a_machine_readable_body(): void
    {
        Queue::fake();

        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->post(route('monitors.check', $monitor));
        }

        $response = $this->actingAs($user)
            ->postJson(route('monitors.check', $monitor));

        $response->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);
    }
}
