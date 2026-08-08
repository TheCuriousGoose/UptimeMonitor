<?php

namespace App\Queries;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Monitor lookups scoped to an owner.
 *
 * The second method is the one that matters: every screen that attaches
 * monitors to something else — a status page, a maintenance window, an
 * integration — receives uuids from the browser and must resolve them against
 * the owning user rather than trusting them. That rule was restated in each
 * controller, which is three chances to get it wrong.
 */
class OwnedMonitors
{
    /**
     * The picker list: everything this user owns, alphabetically.
     *
     * @return EloquentCollection<int, Monitor>
     */
    public function listFor(User $user): EloquentCollection
    {
        return Monitor::query()->forUser($user)->orderBy('name')->get();
    }

    /**
     * Submitted uuids mapped to primary keys, keeping only the monitors the
     * owner actually owns. Anything else silently drops out.
     *
     * @param  array<int, string>  $uuids
     * @return Collection<string, int>
     */
    public function idsByUuid(int $ownerId, array $uuids): Collection
    {
        if ($uuids === []) {
            return collect();
        }

        return Monitor::query()
            ->where('created_by', $ownerId)
            ->whereIn('uuid', $uuids)
            ->pluck('id', 'uuid');
    }
}
