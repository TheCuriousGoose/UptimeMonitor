<?php

namespace Tests\Feature\Monitors;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorSearchTest extends TestCase
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

    public function test_it_matches_on_name(): void
    {
        $user = $this->user();

        Monitor::factory()->forUser($user)->create(['name' => 'Checkout API']);
        Monitor::factory()->forUser($user)->create(['name' => 'Marketing site']);

        $response = $this->actingAs($user)->getJson(route('monitors.search', ['q' => 'checkout']));

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertSame('Checkout API', $response->json('0.name'));
    }

    public function test_it_returns_only_the_fields_the_palette_renders(): void
    {
        $user = $this->user();
        Monitor::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->getJson(route('monitors.search'));

        // A navigation aid, not a second read API — no url, config or history.
        $this->assertSame(['uuid', 'name', 'status'], array_keys($response->json('0')));
    }

    public function test_it_never_returns_another_users_monitors(): void
    {
        $user = $this->user();
        Monitor::factory()->forUser($this->user())->create(['name' => 'Secret service']);

        $response = $this->actingAs($user)->getJson(route('monitors.search', ['q' => 'Secret']));

        $response->assertOk()->assertExactJson([]);
    }

    public function test_a_wildcard_cannot_widen_the_search(): void
    {
        $user = $this->user();
        Monitor::factory()->forUser($user)->create(['name' => 'Checkout API']);

        $response = $this->actingAs($user)->getJson(route('monitors.search', ['q' => '%']));

        $response->assertOk()->assertExactJson([]);
    }

    public function test_it_caps_the_result_count(): void
    {
        $user = $this->user();
        Monitor::factory()->count(15)->forUser($user)->create();

        $response = $this->actingAs($user)->getJson(route('monitors.search'));

        $this->assertCount(10, $response->json());
    }

    public function test_it_requires_authentication(): void
    {
        $this->get(route('monitors.search'))->assertRedirect(route('login'));
    }
}
