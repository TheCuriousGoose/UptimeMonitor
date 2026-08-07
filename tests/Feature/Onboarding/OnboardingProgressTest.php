<?php

namespace Tests\Feature\Onboarding;

use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\StatusPage;
use App\Models\User;
use App\Onboarding\OnboardingProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Progress is read back off the account rather than stored, so the cases worth
 * pinning are that each step reflects real state and that one user's setup
 * never counts towards another's.
 */
class OnboardingProgressTest extends TestCase
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

    public function test_a_new_account_has_nothing_done(): void
    {
        $this->assertSame([
            'has_monitor' => false,
            'has_channel' => false,
            'has_status_page' => false,
            'dismissed' => false,
        ], OnboardingProgress::for($this->user()));
    }

    public function test_each_step_follows_what_the_account_has(): void
    {
        $user = $this->user();

        Monitor::factory()->forUser($user)->create();
        NotificationChannel::factory()->create(['user_id' => $user->id]);
        StatusPage::factory()->create(['user_id' => $user->id]);

        $progress = OnboardingProgress::for($user);

        $this->assertTrue($progress['has_monitor']);
        $this->assertTrue($progress['has_channel']);
        $this->assertTrue($progress['has_status_page']);
    }

    public function test_another_users_setup_does_not_count(): void
    {
        $stranger = $this->user();

        Monitor::factory()->forUser($stranger)->create();
        NotificationChannel::factory()->create(['user_id' => $stranger->id]);
        StatusPage::factory()->create(['user_id' => $stranger->id]);

        $progress = OnboardingProgress::for($this->user());

        $this->assertFalse($progress['has_monitor']);
        $this->assertFalse($progress['has_channel']);
        $this->assertFalse($progress['has_status_page']);
    }

    public function test_deleting_the_last_integration_reopens_that_step(): void
    {
        $user = $this->user();
        $channel = NotificationChannel::factory()->create(['user_id' => $user->id]);

        $this->assertTrue(OnboardingProgress::for($user)['has_channel']);

        $channel->delete();

        $this->assertFalse(OnboardingProgress::for($user->fresh())['has_channel']);
    }

    public function test_the_dashboard_reports_progress(): void
    {
        $user = $this->user();

        // With a monitor the dashboard renders rather than handing off to the
        // guided setup, and can be asked what is still outstanding.
        Monitor::factory()->forUser($user)->create();

        $props = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertTrue($props['onboarding']['has_monitor']);
        $this->assertFalse($props['onboarding']['has_channel']);
    }

    public function test_a_user_can_dismiss_the_checklist(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->patchJson('/me/preferences', [
                OnboardingProgress::PREFERENCE_KEY => true,
            ])
            ->assertOk();

        $this->assertTrue(OnboardingProgress::for($user->fresh())['dismissed']);
    }

    public function test_dismissing_leaves_other_preferences_alone(): void
    {
        $user = $this->user();
        $user->preferences = ['columns' => ['monitors' => ['name' => true]]];
        $user->save();

        $this->actingAs($user)
            ->patchJson('/me/preferences', [
                OnboardingProgress::PREFERENCE_KEY => true,
            ])
            ->assertOk();

        $fresh = $user->fresh();

        $this->assertTrue($fresh->preferences[OnboardingProgress::PREFERENCE_KEY]);
        $this->assertSame(['name' => true], $fresh->preferences['columns']['monitors']);
    }
}
