<?php

namespace Tests\Feature\Api\V1;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        RateLimiter::clear('api');
        RateLimiter::clear('check-now');
    }

    private function user(): User
    {
        return User::factory()->withRole('User')->create();
    }

    public function test_rate_limit_headers_are_exposed(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user, ['monitors:read']);

        $response = $this->getJson(route('api.v1.monitors.index'));

        $response->assertOk()
            ->assertHeader('X-RateLimit-Limit', 120);

        $this->assertLessThan(120, (int) $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_exceeding_the_api_limit_returns_a_json_429(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user, ['monitors:read']);

        for ($i = 0; $i < 120; $i++) {
            $this->getJson(route('api.v1.monitors.index'));
        }

        $this->getJson(route('api.v1.monitors.index'))
            ->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after'])
            ->assertHeader('Retry-After');
    }

    /**
     * check-now is keyed by user rather than token, so minting extra keys must
     * not raise how many outbound checks one account can force.
     */
    public function test_the_check_now_limit_is_shared_across_an_accounts_tokens(): void
    {
        Queue::fake();

        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        Sanctum::actingAs($user, ['checks:trigger']);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson(route('api.v1.monitors.check', $monitor))->assertAccepted();
        }

        // A different token for the same user gets no fresh budget.
        Sanctum::actingAs($user, ['checks:trigger']);

        $this->postJson(route('api.v1.monitors.check', $monitor))->assertStatus(429);
    }
}
