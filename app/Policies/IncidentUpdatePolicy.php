<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\IncidentUpdate;
use App\Models\User;

class IncidentUpdatePolicy
{
    /**
     * The author, or whoever owns the monitor the incident belongs to.
     */
    public function update(User $user, IncidentUpdate $update): bool
    {
        return ($update->user_id === $user->id
                || $update->incident->monitor->created_by === $user->id)
            && $user->can(Permission::MonitorsEdit->value);
    }

    public function delete(User $user, IncidentUpdate $update): bool
    {
        return $this->update($user, $update);
    }
}
