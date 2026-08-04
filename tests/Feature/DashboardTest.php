<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_it_summarises_the_users_monitors(): void
    {
        $user = User::factory()->create();

        Monitor::factory()->forUser($user)->up()->count(2)->create();
        Monitor::factory()->forUser($user)->down()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('summary.total', 3)
                ->where('summary.up', 2)
                ->where('summary.down', 1)
                ->has('attention', 1)
            );
    }

    public function test_it_only_reports_the_signed_in_users_data(): void
    {
        $user = User::factory()->create();
        Monitor::factory()->forUser(User::factory()->create())->down()->count(5)->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('summary.total', 0)
                ->has('attention', 0)
                ->has('recentIncidents', 0)
            );
    }

    public function test_it_lists_recent_incidents_with_their_monitor(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->forUser($user)->down()->create(['name' => 'Checkout API']);

        Incident::factory()->create(['monitor_id' => $monitor->id]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('recentIncidents', 1)
                ->where('recentIncidents.0.monitor.name', 'Checkout API')
                ->where('recentIncidents.0.is_ongoing', true)
            );
    }

    public function test_paused_monitors_are_not_flagged_for_attention(): void
    {
        $user = User::factory()->create();
        Monitor::factory()->forUser($user)->paused()->create(['latest_is_up' => false]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('attention', 0)
                ->where('summary.paused', 1)
            );
    }
}
