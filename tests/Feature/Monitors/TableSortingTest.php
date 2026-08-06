<?php

namespace Tests\Feature\Monitors;

use App\Models\Incident;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sort columns arrive from the client and end up inside orderBy, so the
 * allowlist is a security control, not a convenience.
 */
class TableSortingTest extends TestCase
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

    /**
     * @return array<int, string>
     */
    private function names(User $user, array $query): array
    {
        $props = $this->actingAs($user)
            ->get(route('monitors.index', $query))
            ->viewData('page')['props'];

        return array_column($props['monitors']['data'], 'name');
    }

    public function test_monitors_sort_by_name_in_both_directions(): void
    {
        $user = $this->user();

        foreach (['Charlie', 'Alpha', 'Bravo'] as $name) {
            Monitor::factory()->forUser($user)->create(['name' => $name]);
        }

        $this->assertSame(
            ['Alpha', 'Bravo', 'Charlie'],
            $this->names($user, ['sort' => 'name', 'direction' => 'asc']),
        );

        $this->assertSame(
            ['Charlie', 'Bravo', 'Alpha'],
            $this->names($user, ['sort' => 'name', 'direction' => 'desc']),
        );
    }

    public function test_an_unknown_sort_column_is_rejected(): void
    {
        $user = $this->user();
        Monitor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->get(route('monitors.index', ['sort' => 'created_by']))
            ->assertSessionHasErrors('sort');
    }

    /**
     * The one that matters: if the column ever reached orderBy unfiltered,
     * this payload would be a SQL injection.
     */
    public function test_a_sql_payload_in_the_sort_column_is_rejected(): void
    {
        $user = $this->user();
        Monitor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->get(route('monitors.index', ['sort' => 'name); DROP TABLE monitors; --']))
            ->assertSessionHasErrors('sort');

        $this->assertTrue(Monitor::query()->exists());
    }

    public function test_an_unknown_direction_is_rejected(): void
    {
        $user = $this->user();
        Monitor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->get(route('monitors.index', ['sort' => 'name', 'direction' => 'sideways']))
            ->assertSessionHasErrors('direction');
    }

    public function test_no_sort_keeps_the_down_first_default(): void
    {
        $user = $this->user();

        Monitor::factory()->forUser($user)->create(['name' => 'Healthy', 'latest_is_up' => true]);
        Monitor::factory()->forUser($user)->create(['name' => 'Broken', 'latest_is_up' => false]);
        Monitor::factory()->forUser($user)->create(['name' => 'Asleep', 'is_active' => false]);

        $this->assertSame(['Broken', 'Healthy', 'Asleep'], $this->names($user, []));
    }

    public function test_incidents_sort_by_duration_with_open_ones_counted_as_still_running(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        // Resolved after a minute.
        Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'started_at' => now()->subMinutes(10),
            'resolved_at' => now()->subMinutes(9),
        ]);

        // Still open for an hour, so it is the longer of the two.
        Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'started_at' => now()->subHour(),
            'resolved_at' => null,
        ]);

        $props = $this->actingAs($user)
            ->get(route('incidents.index', ['sort' => 'duration', 'direction' => 'desc']))
            ->viewData('page')['props'];

        $first = $props['incidents']['data'][0];

        $this->assertTrue($first['is_ongoing'], 'The open incident is still growing and sorts longest.');
    }

    public function test_incidents_sort_by_monitor_name(): void
    {
        $user = $this->user();

        foreach (['Zulu', 'Alpha'] as $name) {
            $monitor = Monitor::factory()->forUser($user)->create(['name' => $name]);
            Incident::factory()->create(['monitor_id' => $monitor->id]);
        }

        $props = $this->actingAs($user)
            ->get(route('incidents.index', ['sort' => 'monitor', 'direction' => 'asc']))
            ->viewData('page')['props'];

        $this->assertSame('Alpha', $props['incidents']['data'][0]['monitor']['name']);
    }

    public function test_the_sort_survives_pagination_links(): void
    {
        $user = $this->user();
        Monitor::factory()->count(20)->forUser($user)->create();

        $props = $this->actingAs($user)
            ->get(route('monitors.index', ['sort' => 'name', 'direction' => 'desc']))
            ->viewData('page')['props'];

        $this->assertStringContainsString('sort=name', $props['monitors']['links']['next']);
        $this->assertSame('name', $props['filters']['sort']);
        $this->assertSame('desc', $props['filters']['direction']);
    }
}
