<?php

namespace App\Policies\Concerns;

use App\Enums\Permission;
use App\Models\User;

/**
 * The rule nearly every resource policy here enforces: you may act on a record
 * if you own it *and* your role carries the permission.
 *
 * A trait rather than a base policy on purpose. Policies differ in which
 * abilities they actually have — an incident has no create, a monitor's
 * viewAny is ungated — and inheriting a fixed set of five methods would grant
 * abilities that were never meant to exist. This supplies the conjunction and
 * nothing else, so each policy still spells out its own surface.
 */
trait AuthorizesOwnership
{
    /**
     * Both halves are required: ownership without the permission is a user
     * whose role was narrowed, and the permission without ownership is
     * somebody else's record.
     */
    protected function owns(User $user, ?int $ownerId, Permission $permission): bool
    {
        return $ownerId !== null
            && $ownerId === $user->id
            && $user->can($permission->value);
    }
}
