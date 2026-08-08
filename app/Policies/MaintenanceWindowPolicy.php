<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\MaintenanceWindow;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOwnership;

class MaintenanceWindowPolicy
{
    use AuthorizesOwnership;

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
        return $this->owns($user, $window->user_id, Permission::MaintenanceEdit);
    }

    public function delete(User $user, MaintenanceWindow $window): bool
    {
        return $this->owns($user, $window->user_id, Permission::MaintenanceDelete);
    }
}
