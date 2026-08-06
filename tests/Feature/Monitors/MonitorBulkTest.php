<?php

namespace Tests\Feature\Monitors;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorBulkTest extends TestCase
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

    private function bulk(User $user, string $action, array $uuids)
    {
        return $this->actingAs($user)->post(route('monitors.bulk'), [
            'action' => $action,
            'monitors' => $uuids,
        ]);
    }

    public function test_it_pauses_every_selected_monitor(): void
    {
        $user = $this->user();
        $monitors = Monitor::factory()->count(3)->forUser($user)->create(['is_active' => true]);

        $this->bulk($user, 'pause', $monitors->pluck('uuid')->all())->assertRedirect();

        $this->assertSame(0, Monitor::query()->forUser($user)->where('is_active', true)->count());
    }

    public function test_resuming_schedules_the_next_check_immediately(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create([
            'is_active' => false,
            'next_check_at' => now()->addHour(),
            'failure_streak' => 4,
        ]);

        $this->bulk($user, 'resume', [$monitor->uuid])->assertRedirect();

        $monitor->refresh();

        $this->assertTrue($monitor->is_active);
        $this->assertSame(0, $monitor->failure_streak);
        $this->assertTrue($monitor->next_check_at->lessThanOrEqualTo(now()));
    }

    public function test_it_deletes_every_selected_monitor(): void
    {
        $user = $this->user();
        $monitors = Monitor::factory()->count(2)->forUser($user)->create();

        $this->bulk($user, 'delete', $monitors->pluck('uuid')->all())->assertRedirect();

        $this->assertSame(0, Monitor::query()->forUser($user)->count());
    }

    /**
     * The important one. Uuids are resolved through forUser() rather than
     * trusted, so a crafted request naming someone else's monitor changes
     * nothing at all.
     */
    public function test_it_never_touches_another_users_monitors(): void
    {
        $user = $this->user();
        $stranger = $this->user();

        $mine = Monitor::factory()->forUser($user)->create(['is_active' => true]);
        $theirs = Monitor::factory()->forUser($stranger)->create(['is_active' => true]);

        $this->bulk($user, 'pause', [$mine->uuid, $theirs->uuid])->assertRedirect();

        $this->assertFalse($mine->fresh()->is_active);
        $this->assertTrue($theirs->fresh()->is_active, "Another user's monitor must be untouched.");
    }

    public function test_a_selection_of_only_foreign_monitors_changes_nothing(): void
    {
        $user = $this->user();
        $theirs = Monitor::factory()->forUser($this->user())->create();

        $this->bulk($user, 'delete', [$theirs->uuid])->assertRedirect();

        $this->assertModelExists($theirs);
    }

    public function test_it_rejects_an_unknown_action(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        $this->bulk($user, 'destroy_everything', [$monitor->uuid])
            ->assertSessionHasErrors('action');
    }

    public function test_it_caps_the_batch_size(): void
    {
        $user = $this->user();

        $uuids = collect(range(1, 101))->map(fn () => fake()->uuid())->all();

        $this->bulk($user, 'pause', $uuids)->assertSessionHasErrors('monitors');
    }

    public function test_it_requires_authentication(): void
    {
        $this->post(route('monitors.bulk'), ['action' => 'pause', 'monitors' => []])
            ->assertRedirect(route('login'));
    }
}
