<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\IncidentUpdate;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOwnership;

class IncidentUpdatePolicy
{
    use AuthorizesOwnership;

    /**
     * The author, or whoever owns the monitor the incident belongs to.
     */
    public function update(User $user, IncidentUpdate $update): bool
    {
        return $this->owns($user, $update->user_id, Permission::IncidentsComment)
            || $this->owns($user, $update->incident?->monitor?->created_by, Permission::IncidentsComment);
    }

    public function delete(User $user, IncidentUpdate $update): bool
    {
        return $this->update($user, $update);
    }
}
