<?php

namespace Tests\Feature\Monitors;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_gets_redirected_when_not_logged_in(): void
    {
        $response = $this->get(route('monitors.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_see_monitors(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownMonitor = Monitor::factory()->forUser($user)->create();

        $otherMonitor = Monitor::factory()->forUser($otherUser)->create();

        $response = $this->actingAs($user)->get(route('monitors.index'));

        $response->assertOk()
            ->assertSee($ownMonitor->name)
            ->assertDontSee($otherMonitor->name);
    }

    public function test_authenticated_user_can_see_monitors_even_if_monitor_has_no_checks(): void
    {
        $user = User::factory()->create();

        $monitorWithChecks = Monitor::factory()->forUser($user)->create();

        $monitorWithoutChecks = Monitor::factory()->forUser($user)->create();

        MonitorCheck::factory()->create([
            'monitor_id' => $monitorWithChecks->id,
        ]);

        $response = $this->actingAs($user)->get(route('monitors.index'));

        $response->assertOk()
            ->assertSee($monitorWithChecks->name)
            ->assertSee($monitorWithoutChecks->name);
    }

    public function test_authenticated_user_can_see_create_monitor_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('monitors.create'));

        $response->assertOk();
    }

    public function test_authenticated_user_can_see_monitor_details(): void
    {
        $user = User::factory()->create();

        $monitor = Monitor::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->get(route('monitors.show', $monitor->uuid));

        $response->assertOk();
    }

    public function test_authenticated_user_cannot_see_monitor_details_of_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $monitor = Monitor::factory()->forUser($otherUser)->create();

        $response = $this->actingAs($user)->get(route('monitors.show', $monitor));

        $response->assertForbidden();
    }
}
