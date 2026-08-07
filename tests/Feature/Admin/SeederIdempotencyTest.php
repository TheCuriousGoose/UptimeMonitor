<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\AlertingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerting_seeder_can_run_twice(): void
    {
        User::factory()->create();

        $this->seed(AlertingSeeder::class);
        $this->seed(AlertingSeeder::class);

        $this->assertDatabaseCount('status_pages', 1);
        $this->assertDatabaseCount('notification_channels', 1);
    }

    public function test_reseeding_keeps_permissions_granted_from_the_roles_screen(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::where('name', RoleEnum::User->value)->firstOrFail();
        $role->revokePermissionTo('monitors.delete');
        $role->givePermissionTo('users.view');

        $this->seed(RolesAndPermissionsSeeder::class);

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('users.view'));
        $this->assertFalse($role->hasPermissionTo('monitors.delete'));
    }

    public function test_default_roles_start_with_their_full_permission_set(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::where('name', RoleEnum::User->value)->firstOrFail();

        $this->assertTrue($role->hasPermissionTo('monitors.create'));
        $this->assertTrue($role->hasPermissionTo('status_pages.create'));
        $this->assertTrue($role->hasPermissionTo('maintenance.create'));
        $this->assertTrue($role->hasPermissionTo('incidents.acknowledge'));
    }
}
