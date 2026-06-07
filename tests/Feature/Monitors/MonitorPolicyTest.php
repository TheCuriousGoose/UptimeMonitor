<?php

namespace Tests\Feature\Monitors;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonitorPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'Super Admin']);
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'User']);

        // Create permission for monitor creation
        Permission::create(['name' => 'monitors.create']);
    }

    public function test_gets_redirected_when_not_logged_in(): void
    {
        $response = $this->get(route('monitors.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_see_monitors(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        $user->givePermissionTo('monitors.create');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('User');
        $otherUser->givePermissionTo('monitors.create');

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
        $user->assignRole('User');
        $user->givePermissionTo('monitors.create');

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
        $user->assignRole('User');
        $user->givePermissionTo('monitors.create');

        $response = $this->actingAs($user)->get(route('monitors.create'));

        $response->assertOk();
    }

    public function test_user_without_create_permission_cannot_create_monitor(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        // No permission to create

        $response = $this->actingAs($user)->get(route('monitors.create'));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_see_monitor_details(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        $user->givePermissionTo('monitors.create');

        $monitor = Monitor::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->get(route('monitors.show', $monitor->uuid));

        $response->assertOk();
    }

    public function test_authenticated_user_cannot_see_monitor_details_of_other_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('User');

        $monitor = Monitor::factory()->forUser($otherUser)->create();

        $response = $this->actingAs($user)->get(route('monitors.show', $monitor));

        $response->assertForbidden();
    }

    public function test_super_admin_can_view_any_monitor(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('User');

        $monitor = Monitor::factory()->forUser($otherUser)->create();

        $response = $this->actingAs($admin)->get(route('monitors.show', $monitor));

        $response->assertOk();
    }

    public function test_super_admin_can_update_any_monitor(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('User');

        $monitor = Monitor::factory()->forUser($otherUser)->create();

        // Super admin should be able to access edit page
        $response = $this->actingAs($admin)->get(route('monitors.edit', $monitor));

        $response->assertOk();
    }

    public function test_regular_user_cannot_edit_other_users_monitor(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('User');

        $monitor = Monitor::factory()->forUser($otherUser)->create();

        $response = $this->actingAs($user)->get(route('monitors.edit', $monitor));

        $response->assertForbidden();
    }
}
