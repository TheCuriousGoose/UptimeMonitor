<?php

namespace Tests\Feature\Monitors;

use App\Enums\AlertScope;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A monitor nothing is listening to detects an outage and then tells nobody,
 * which is worse than not having it — so its own page has to say so. The catch
 * is that an all-scope channel covers a monitor without ever appearing on the
 * pivot, so this cannot be read off the relation.
 */
class MonitorAlertCoverageTest extends TestCase
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

    private function covered(User $user, Monitor $monitor): bool
    {
        return $this->actingAs($user)
            ->get(route('monitors.show', $monitor))
            ->assertOk()
            ->viewData('page')['props']['alertsCovered'];
    }

    public function test_a_monitor_with_no_channels_is_not_covered(): void
    {
        $user = $this->user();

        $this->assertFalse(
            $this->covered($user, Monitor::factory()->forUser($user)->create()),
        );
    }

    public function test_an_attached_channel_covers_it(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        $channel = NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'alert_scope' => AlertScope::Selected,
        ]);
        $monitor->notificationChannels()->attach($channel);

        $this->assertTrue($this->covered($user, $monitor));
    }

    public function test_an_all_scope_channel_covers_it_without_being_attached(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'alert_scope' => AlertScope::All,
        ]);

        $this->assertTrue($this->covered($user, $monitor));
    }

    public function test_a_channel_scoped_to_other_monitors_does_not_cover_it(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();
        $other = Monitor::factory()->forUser($user)->create();

        $channel = NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'alert_scope' => AlertScope::Selected,
        ]);
        $other->notificationChannels()->attach($channel);

        $this->assertFalse($this->covered($user, $monitor));
    }

    public function test_a_paused_channel_does_not_count_as_coverage(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'alert_scope' => AlertScope::All,
            'is_active' => false,
        ]);

        $this->assertFalse($this->covered($user, $monitor));
    }

    public function test_another_users_channel_does_not_count(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        NotificationChannel::factory()->create([
            'user_id' => $this->user()->id,
            'alert_scope' => AlertScope::All,
        ]);

        $this->assertFalse($this->covered($user, $monitor));
    }
}
