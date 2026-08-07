<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::IncidentsView->value);
    }

    public function view(User $user, Incident $incident): bool
    {
        return $incident->monitor->created_by === $user->id
            && $user->can(Permission::IncidentsView->value);
    }

    public function acknowledge(User $user, Incident $incident): bool
    {
        return $incident->monitor->created_by === $user->id
            && $user->can(Permission::IncidentsAcknowledge->value);
    }

    public function comment(User $user, Incident $incident): bool
    {
        return $incident->monitor->created_by === $user->id
            && $user->can(Permission::IncidentsComment->value);
    }
}
