<?php

namespace Tests\Feature\Maintenance;

use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceWindowCrudTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sunday deploy',
            'recurrence' => 'once',
            'timezone' => 'UTC',
            'starts_at' => now()->addHour()->toDateTimeString(),
            'ends_at' => now()->addHours(2)->toDateTimeString(),
        ], $overrides);
    }

    public function test_a_user_can_create_a_one_off_window(): void
    {
        $this->actingAs($this->user())
            ->post(route('maintenance-windows.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('maintenance_windows', ['name' => 'Sunday deploy']);
    }

    public function test_a_recurring_window_needs_a_cron_and_a_duration(): void
    {
        $this->actingAs($this->user())
            ->post(route('maintenance-windows.store'), $this->payload(['recurrence' => 'recurring']))
            ->assertSessionHasErrors(['cron', 'duration_minutes']);
    }

    public function test_it_rejects_an_invalid_cron(): void
    {
        $this->actingAs($this->user())
            ->post(route('maintenance-windows.store'), $this->payload([
                'recurrence' => 'recurring',
                'cron' => 'not a cron',
                'duration_minutes' => 60,
            ]))
            ->assertSessionHasErrors('cron');
    }

    public function test_a_one_off_window_must_end_after_it_starts(): void
    {
        $this->actingAs($this->user())
            ->post(route('maintenance-windows.store'), $this->payload([
                'ends_at' => now()->toDateTimeString(),
            ]))
            ->assertSessionHasErrors('ends_at');
    }

    public function test_switching_to_recurring_clears_the_one_off_dates(): void
    {
        $user = $this->user();
        $window = MaintenanceWindow::factory()->for($user)->create();

        $this->actingAs($user)
            ->put(route('maintenance-windows.update', $window), $this->payload([
                'recurrence' => 'recurring',
                'cron' => '0 2 * * 0',
                'duration_minutes' => 60,
            ]))
            ->assertSessionHasNoErrors();

        $window->refresh();

        $this->assertNull($window->starts_at);
        $this->assertNull($window->ends_at);
        $this->assertSame('0 2 * * 0', $window->cron);
    }

    public function test_it_only_attaches_monitors_the_user_owns(): void
    {
        $user = $this->user();
        $mine = Monitor::factory()->forUser($user)->create();
        $theirs = Monitor::factory()->forUser($this->user())->create();

        $this->actingAs($user)
            ->post(route('maintenance-windows.store'), $this->payload([
                'monitors' => [$mine->uuid, $theirs->uuid],
            ]))
            ->assertSessionHasNoErrors();

        $window = MaintenanceWindow::query()->latest('id')->first();

        $this->assertSame([$mine->id], $window->monitors->pluck('id')->all());
    }

    public function test_a_stranger_cannot_touch_another_users_window(): void
    {
        $window = MaintenanceWindow::factory()->for($this->user())->create();

        $this->actingAs($this->user())
            ->put(route('maintenance-windows.update', $window), $this->payload())
            ->assertForbidden();

        $this->actingAs($this->user())
            ->delete(route('maintenance-windows.destroy', $window))
            ->assertForbidden();
    }

    public function test_the_index_lists_only_the_users_own_windows(): void
    {
        $user = $this->user();
        MaintenanceWindow::factory()->for($user)->create(['name' => 'Mine']);
        MaintenanceWindow::factory()->for($this->user())->create(['name' => 'Theirs']);

        $response = $this->actingAs($user)->get(route('maintenance-windows.index'));

        $response->assertOk();

        $props = $response->viewData('page')['props'];

        $this->assertSame(['Mine'], array_column($props['windows'], 'name'));
    }

    public function test_a_window_can_be_deleted(): void
    {
        $user = $this->user();
        $window = MaintenanceWindow::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('maintenance-windows.destroy', $window))
            ->assertRedirect();

        $this->assertModelMissing($window);
    }
}
