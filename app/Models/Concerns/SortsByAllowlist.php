<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ordering driven by a client-supplied column name, safely.
 *
 * The allowlist is the whole point. `direction` is checked against two
 * literals, but the column name would otherwise reach orderBy() as-is, so a
 * request naming any string would interpolate it. Only keys present in the
 * model's `SORTS` map are honoured, and anything else falls through to the
 * model's default ranking rather than being passed along.
 *
 * A model using this must declare a `SORTS` constant mapping the name the
 * table sends to a real column, or to null when the ordering is derived
 * rather than stored — those land in {@see sortDerived()}.
 */
trait SortsByAllowlist
{
    #[Scope]
    protected function sort(Builder $query, ?string $sort, string $direction = 'asc'): void
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $column = static::SORTS[$sort] ?? null;

        if ($column !== null) {
            $query->orderBy($column, $direction);
        } else {
            $this->sortDerived($query, $sort, $direction);
        }

        // Always last, so a page boundary cannot land mid-tie and show the
        // same row twice.
        $this->sortTiebreak($query);
    }

    /**
     * Orderings that are computed rather than stored — a column on a related
     * table, a rank, a duration. Reached only for a name the allowlist maps
     * to null, so it never sees arbitrary input.
     */
    abstract protected function sortDerived(Builder $query, ?string $sort, string $direction): void;

    abstract protected function sortTiebreak(Builder $query): void;
}
