<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::RolesView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::RolesCreate->value);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(Permission::RolesEdit->value)
            && ! $this->isSuperAdmin($role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can(Permission::RolesDelete->value)
            && ! $this->isSuperAdmin($role);
    }

    // Without this, roles.edit is a universal escalation: mint a role
    // carrying every permission, then wear it.
    public function grantPermission(User $user, string $permission): bool
    {
        return $user->can($permission);
    }

    private function isSuperAdmin(Role $role): bool
    {
        return $role->name === RoleEnum::SuperAdmin->value;
    }
}
