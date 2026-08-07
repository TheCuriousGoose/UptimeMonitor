<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function userWith(string ...$permissions): User
    {
        $role = Role::create(['name' => 'Scoped '.uniqid()]);
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_screens_are_reachable_with_only_the_matching_permission(): void
    {
        $this->actingAs($this->userWith('users.view'))
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->actingAs($this->userWith('roles.view'))
            ->get(route('admin.roles.index'))
            ->assertOk();

        $this->actingAs($this->userWith('settings.view'))
            ->get(route('admin.settings.index'))
            ->assertOk();
    }

    public function test_admin_screens_are_closed_without_the_permission(): void
    {
        $user = $this->userWith('monitors.view');

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.settings.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.content.index'))->assertForbidden();
    }

    public function test_settings_cannot_be_changed_with_view_permission_alone(): void
    {
        $this->actingAs($this->userWith('settings.view'))
            ->put(route('admin.settings.update', 'oauth.google'), ['value' => true])
            ->assertForbidden();
    }

    public function test_role_cannot_be_granted_permissions_the_actor_lacks(): void
    {
        $user = $this->userWith('roles.view', 'roles.create', 'roles.edit');
        $escalation = Permission::where('name', 'users.edit')->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.roles.store'), [
                'name' => 'Escalated',
                'permissions' => [$escalation->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'Escalated']);
    }

    public function test_role_can_be_granted_permissions_the_actor_holds(): void
    {
        $user = $this->userWith('roles.view', 'roles.create', 'monitors.view');
        $held = Permission::where('name', 'monitors.view')->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.roles.store'), [
                'name' => 'Monitor Reader',
                'permissions' => [$held->id],
            ])
            ->assertRedirect();

        $this->assertTrue(
            Role::where('name', 'Monitor Reader')->firstOrFail()->hasPermissionTo('monitors.view'),
        );
    }

    public function test_super_admin_role_cannot_be_edited_or_deleted(): void
    {
        $user = $this->userWith('roles.view', 'roles.edit', 'roles.delete');
        $superAdmin = Role::where('name', 'Super Admin')->firstOrFail();

        $this->actingAs($user)
            ->put(route('admin.roles.update', $superAdmin), ['name' => 'Pwned', 'permissions' => []])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.roles.destroy', $superAdmin))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['name' => 'Super Admin']);
    }

    public function test_super_admin_role_cannot_be_self_assigned(): void
    {
        $user = $this->userWith('users.view', 'users.edit');
        $superAdmin = Role::where('name', 'Super Admin')->firstOrFail();

        $this->actingAs($user)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => [$superAdmin->id],
            ])
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasRole('Super Admin'));
    }

    public function test_super_admin_account_cannot_be_edited_by_a_lesser_admin(): void
    {
        $user = $this->userWith('users.view', 'users.edit');

        $target = User::factory()->create();
        $target->assignRole('Super Admin');

        $this->actingAs($user)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => 'attacker@example.com',
                'roles' => [],
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.users.password.update', $target), [
                'password' => 'N0t-your-account!',
                'password_confirmation' => 'N0t-your-account!',
            ])
            ->assertForbidden();

        $this->assertSame($target->email, $target->fresh()->email);
    }

    public function test_impersonation_stays_super_admin_only(): void
    {
        $user = $this->userWith('roles.view', 'roles.edit');
        $role = Role::where('name', 'User')->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.impersonate.store', $role))
            ->assertForbidden();
    }

    public function test_super_admin_retains_full_access(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.roles.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.settings.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.content.index'))->assertOk();
    }
}
