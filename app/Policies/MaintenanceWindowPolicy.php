<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\MaintenanceWindow;
use App\Models\User;

/**
 * Reuses the monitor permissions rather than introducing its own: a
 * maintenance window is a property of the monitors it silences, and
 * RolesAndPermissionsSeeder uses syncPermissions(), so a new Permission case
 * would clobber operator-customised roles on the next seed.
 */
class MaintenanceWindowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MonitorsView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MonitorsEdit->value);
    }

    public function update(User $user, MaintenanceWindow $window): bool
    {
        return $window->user_id === $user->id
            && $user->can(Permission::MonitorsEdit->value);
    }

    public function delete(User $user, MaintenanceWindow $window): bool
    {
        return $this->update($user, $window);
    }
}
