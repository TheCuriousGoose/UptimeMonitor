<?php

namespace Tests\Feature\Incidents;

use App\Models\Incident;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class IncidentIndexTest extends TestCase
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

    public function test_the_page_renders_only_the_users_own_incidents(): void
    {
        $user = $this->user();
        $mine = Monitor::factory()->forUser($user)->create();
        $theirs = Monitor::factory()->forUser($this->user())->create();

        Incident::factory()->count(2)->create(['monitor_id' => $mine->id]);
        Incident::factory()->count(3)->create(['monitor_id' => $theirs->id]);

        $this->actingAs($user)
            ->get(route('incidents.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('incidents/Index')
                    ->has('incidents.data', 2)
                    ->where('summary.total', 2),
            );
    }

    public function test_incidents_can_be_filtered_by_status(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        Incident::factory()->create(['monitor_id' => $monitor->id]);
        Incident::factory()->resolved()->create(['monitor_id' => $monitor->id]);

        $this->actingAs($user)
            ->get(route('incidents.index', ['status' => 'ongoing']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('incidents.data', 1)
                    ->where('incidents.data.0.is_ongoing', true),
            );

        $this->actingAs($user)
            ->get(route('incidents.index', ['status' => 'resolved']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('incidents.data', 1)
                    ->where('incidents.data.0.is_ongoing', false),
            );
    }

    public function test_incidents_can_be_searched_by_monitor_name(): void
    {
        $user = $this->user();
        $api = Monitor::factory()->forUser($user)->create(['name' => 'Payments API']);
        $web = Monitor::factory()->forUser($user)->create(['name' => 'Marketing site']);

        Incident::factory()->create(['monitor_id' => $api->id]);
        Incident::factory()->create(['monitor_id' => $web->id]);

        $this->actingAs($user)
            ->get(route('incidents.index', ['search' => 'Payments']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('incidents.data', 1)
                    ->where('incidents.data.0.monitor.name', 'Payments API'),
            );
    }

    /**
     * Open incidents are the ones needing attention, so they sort first
     * regardless of when they started.
     */
    public function test_ongoing_incidents_sort_above_resolved_ones(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        Incident::factory()->resolved()->create([
            'monitor_id' => $monitor->id,
            'started_at' => now()->subMinutes(5),
        ]);
        Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'started_at' => now()->subDays(3),
        ]);

        $this->actingAs($user)
            ->get(route('incidents.index'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('incidents.data.0.is_ongoing', true),
            );
    }

    public function test_the_summary_counts_windows_correctly(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        Incident::factory()->create(['monitor_id' => $monitor->id, 'started_at' => now()->subHours(2)]);
        Incident::factory()->resolved()->create(['monitor_id' => $monitor->id, 'started_at' => now()->subDays(3)]);
        Incident::factory()->resolved()->create(['monitor_id' => $monitor->id, 'started_at' => now()->subDays(30)]);

        $this->actingAs($user)
            ->get(route('incidents.index'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('summary.ongoing', 1)
                    ->where('summary.last_24h', 1)
                    ->where('summary.last_7d', 2)
                    ->where('summary.total', 3),
            );
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('incidents.index'))->assertRedirect(route('login'));
    }
}
