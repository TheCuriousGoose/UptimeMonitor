<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Incident;
use App\Models\User;

/**
 * Incidents have no CRUD of their own — they are derived from monitor
 * checks — so read access simply mirrors monitor ownership and the
 * existing "view monitors" permission.
 */
class IncidentPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, Incident $incident): bool
    {
        return $incident->monitor->created_by === $user->id
            && $user->can(Permission::MonitorsView->value);
    }
}
