<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Incident;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOwnership;

/**
 * An incident is owned by whoever owns the monitor it belongs to — it has no
 * owner column of its own.
 */
class IncidentPolicy
{
    use AuthorizesOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::IncidentsView->value);
    }

    public function view(User $user, Incident $incident): bool
    {
        return $this->owns($user, $this->ownerOf($incident), Permission::IncidentsView);
    }

    public function acknowledge(User $user, Incident $incident): bool
    {
        return $this->owns($user, $this->ownerOf($incident), Permission::IncidentsAcknowledge);
    }

    public function comment(User $user, Incident $incident): bool
    {
        return $this->owns($user, $this->ownerOf($incident), Permission::IncidentsComment);
    }

    private function ownerOf(Incident $incident): ?int
    {
        return $incident->monitor?->created_by;
    }
}
