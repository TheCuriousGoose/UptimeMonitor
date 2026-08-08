<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Monitor;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOwnership;

class MonitorPolicy
{
    use AuthorizesOwnership;

    /**
     * Ungated on purpose: the index scopes to the acting user, so an empty
     * list is the honest answer rather than a 403.
     */
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, Monitor $monitor): bool
    {
        return $this->owns($user, $monitor->created_by, Permission::MonitorsView);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MonitorsCreate->value);
    }

    public function update(User $user, Monitor $monitor): bool
    {
        return $this->owns($user, $monitor->created_by, Permission::MonitorsEdit);
    }

    public function delete(User $user, Monitor $monitor): bool
    {
        return $this->owns($user, $monitor->created_by, Permission::MonitorsDelete);
    }

    public function restore(User $user, Monitor $monitor): bool
    {
        return $this->owns($user, $monitor->created_by, Permission::MonitorsDelete);
    }
}
