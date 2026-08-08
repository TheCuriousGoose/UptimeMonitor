<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * A model that carries a uuid alongside its auto-incrementing key, and is
 * addressed by that uuid in URLs.
 *
 * The split matters: the integer key stays the join and foreign-key column,
 * so pivots and indexes are unchanged, while nothing sequential about a
 * record's identity is exposed in a route. Every model here wanted both
 * halves and repeated the same two methods to get them.
 */
trait RoutesByUuid
{
    use HasUuids;

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Only `uuid` is generated. Returning the model's own key here would make
     * HasUuids overwrite the auto-incrementing primary key too.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
