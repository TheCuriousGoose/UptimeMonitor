<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\MaintenanceWindow;
use App\Models\User;

class MaintenanceWindowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MaintenanceView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MaintenanceCreate->value);
    }

    public function update(User $user, MaintenanceWindow $window): bool
    {
        return $window->user_id === $user->id
            && $user->can(Permission::MaintenanceEdit->value);
    }

    public function delete(User $user, MaintenanceWindow $window): bool
    {
        return $window->user_id === $user->id
            && $user->can(Permission::MaintenanceDelete->value);
    }
}
