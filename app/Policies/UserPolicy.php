<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::UsersView->value);
    }

    // Editing or resetting a Super Admin is an account takeover path.
    public function update(User $user, User $target): bool
    {
        return $user->can(Permission::UsersEdit->value)
            && ! $this->outranks($target, $user);
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->can(Permission::UsersEdit->value)
            && ($user->is($target) || ! $this->outranks($target, $user));
    }

    // Without this, users.edit alone is enough to self-promote.
    public function assignRole(User $user, Role $role): bool
    {
        return $role !== Role::SuperAdmin || $user->hasRole(Role::SuperAdmin->value);
    }

    private function outranks(User $target, User $user): bool
    {
        return $target->hasRole(Role::SuperAdmin->value)
            && ! $user->hasRole(Role::SuperAdmin->value);
    }
}
