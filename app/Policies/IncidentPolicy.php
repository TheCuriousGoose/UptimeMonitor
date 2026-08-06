<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Incident;
use App\Models\User;

/**
 * Incidents are derived from monitor checks, so access mirrors monitor
 * ownership and the existing monitor permissions rather than introducing
 * its own. Writes (acknowledging, commenting) follow the same rule.
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

    public function acknowledge(User $user, Incident $incident): bool
    {
        return $incident->monitor->created_by === $user->id
            && $user->can(Permission::MonitorsEdit->value);
    }

    public function comment(User $user, Incident $incident): bool
    {
        return $this->acknowledge($user, $incident);
    }
}
