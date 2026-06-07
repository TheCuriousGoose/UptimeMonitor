<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Monitor;
use App\Models\User;

class MonitorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Monitor $monitor): bool
    {
        return $monitor->created_by === $user->id
            && $user->can(Permission::MonitorsView->value);
    }

    /**
     * Determine whether the user can create the model
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::MonitorsCreate->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Monitor $monitor): bool
    {
        return $monitor->created_by === $user->id
            && $user->can(Permission::MonitorsEdit->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Monitor $monitor): bool
    {
        return $monitor->created_by === $user->id
            && $user->can(Permission::MonitorsDelete->value);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Monitor $monitor): bool
    {
        return $monitor->created_by === $user->id
            && $user->can(Permission::MonitorsDelete->value);
    }
}
