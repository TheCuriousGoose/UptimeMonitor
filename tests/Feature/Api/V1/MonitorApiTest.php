<?php

namespace Tests\Feature\Api\V1;

use App\Enums\MonitorType;
use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonitorApiTest extends TestCase
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

    /**
     * @param  array<int, string>  $abilities
     */
    private function actingWithToken(User $user, array $abilities): User
    {
        Sanctum::actingAs($user, $abilities);

        return $user;
    }

    public function test_a_token_can_list_its_owners_monitors(): void
    {
        $user = $this->user();
        Monitor::factory()->forUser($user)->count(3)->create();
        // Another tenant's data must never appear.
        Monitor::factory()->forUser($this->user())->count(2)->create();

        $this->actingWithToken($user, ['monitors:read']);

        $this->getJson(route('api.v1.monitors.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['uuid', 'name', 'url', 'type', 'status']], 'meta']);
    }

    public function test_a_monitor_can_be_created(): void
    {
        $user = $this->user();
        $this->actingWithToken($user, ['monitors:write']);

        $this->postJson(route('api.v1.monitors.store'), [
            'name' => 'API created',
            'type' => MonitorType::Http->value,
            'url' => 'https://example.com',
            'timeout' => 10,
            'interval_seconds' => 300,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'API created');

        $this->assertDatabaseHas('monitors', [
            'name' => 'API created',
            'created_by' => $user->id,
        ]);
    }

    public function test_a_monitor_can_be_updated_and_deleted(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create(['name' => 'Before']);
        $this->actingWithToken($user, ['monitors:write', 'monitors:read']);

        $this->patchJson(route('api.v1.monitors.update', $monitor), [
            'name' => 'After',
            'type' => $monitor->type->value,
            'url' => $monitor->url,
            'timeout' => $monitor->timeout,
            'interval_seconds' => $monitor->interval_seconds,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'After');

        $this->deleteJson(route('api.v1.monitors.destroy', $monitor))
            ->assertNoContent();

        $this->assertDatabaseMissing('monitors', ['id' => $monitor->id]);
    }

    public function test_state_can_be_toggled(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create(['is_active' => true]);
        $this->actingWithToken($user, ['monitors:write']);

        $this->patchJson(route('api.v1.monitors.state', $monitor))
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_a_check_can_be_triggered(): void
    {
        Queue::fake();

        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();
        $this->actingWithToken($user, ['checks:trigger']);

        $this->postJson(route('api.v1.monitors.check', $monitor))
            ->assertAccepted();

        Queue::assertPushed(RunMonitorCheck::class);
    }

    // -- Authorization ----------------------------------------------------

    public function test_requests_without_a_token_are_rejected(): void
    {
        $this->getJson(route('api.v1.monitors.index'))->assertUnauthorized();
    }

    /**
     * A client that forgets "Accept: application/json" must still get a 401
     * JSON body rather than an HTML redirect to the login page.
     */
    public function test_an_unauthenticated_request_returns_json_even_without_an_accept_header(): void
    {
        $response = $this->get(route('api.v1.monitors.index'), ['Accept' => 'text/html']);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * The scope must gate the route even though the user themselves has full
     * permission — a token narrows what its owner can do, never widens it.
     */
    public function test_a_read_only_token_cannot_write(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();
        $this->actingWithToken($user, ['monitors:read']);

        $this->postJson(route('api.v1.monitors.store'), [
            'name' => 'Nope',
            'type' => MonitorType::Http->value,
            'url' => 'https://example.com',
            'timeout' => 10,
            'interval_seconds' => 300,
        ])->assertForbidden();

        $this->deleteJson(route('api.v1.monitors.destroy', $monitor))->assertForbidden();
        $this->patchJson(route('api.v1.monitors.state', $monitor))->assertForbidden();
    }

    public function test_a_token_without_the_trigger_scope_cannot_run_a_check(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();
        $this->actingWithToken($user, ['monitors:read', 'monitors:write']);

        $this->postJson(route('api.v1.monitors.check', $monitor))->assertForbidden();
    }

    /**
     * Scope alone is not enough — the policy still has to deny another
     * tenant's records.
     */
    public function test_a_token_cannot_reach_another_users_monitor(): void
    {
        $owner = $this->user();
        $attacker = $this->user();
        $monitor = Monitor::factory()->forUser($owner)->create();

        $this->actingWithToken($attacker, ['monitors:read', 'monitors:write']);

        $this->getJson(route('api.v1.monitors.show', $monitor))->assertForbidden();
        $this->deleteJson(route('api.v1.monitors.destroy', $monitor))->assertForbidden();

        $this->assertDatabaseHas('monitors', ['id' => $monitor->id]);
    }

    public function test_validation_errors_are_returned_as_json(): void
    {
        $user = $this->user();
        $this->actingWithToken($user, ['monitors:write']);

        $this->postJson(route('api.v1.monitors.store'), ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type', 'url']);
    }

    public function test_per_page_is_capped(): void
    {
        $user = $this->user();
        Monitor::factory()->forUser($user)->count(5)->create();
        $this->actingWithToken($user, ['monitors:read']);

        $this->getJson(route('api.v1.monitors.index', ['per_page' => 5000]))
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }
}
