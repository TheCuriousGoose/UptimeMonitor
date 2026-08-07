<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $introduced = $this->syncPermissionCatalog();

        foreach (Role::cases() as $role) {
            $model = RoleModel::firstOrCreate(['name' => $role->value]);
            $defaults = array_map(fn (Permission $p) => $p->value, $role->permissions());

            // Existing roles are operator-owned: reseeding must not undo
            // grants made from the Roles screen.
            $model->wasRecentlyCreated
                ? $model->syncPermissions($defaults)
                : $model->givePermissionTo(array_intersect($defaults, $introduced));
        }
    }

    /**
     * @return array<int, string> permission names that did not exist before
     */
    private function syncPermissionCatalog(): array
    {
        $introduced = [];

        foreach (Permission::cases() as $permission) {
            $model = PermissionModel::firstOrCreate(['name' => $permission->value]);

            if ($model->wasRecentlyCreated) {
                $introduced[] = $permission->value;
            }
        }

        return $introduced;
    }
}
