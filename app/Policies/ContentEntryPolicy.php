<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Content is site-wide rather than per-tenant, so these are plain permission
 * checks with no ownership component.
 */
class ContentEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ContentView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ContentCreate->value);
    }

    public function update(User $user): bool
    {
        return $user->can(Permission::ContentEdit->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(Permission::ContentDelete->value);
    }
}
