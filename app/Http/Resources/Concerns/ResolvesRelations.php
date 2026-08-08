<?php

namespace App\Http\Resources\Concerns;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/**
 * Includes a nested resource only when its relation was eager loaded, already
 * flattened.
 *
 * The `resolve()` is the part that matters and the part that was easy to
 * forget: a resource left as an object picks up its own "data" envelope when
 * serialised, which the Vue pages do not expect and which breaks them
 * silently. Every resource repeated both the call and a comment explaining
 * it; now the explanation lives here and the call cannot be got wrong.
 */
trait ResolvesRelations
{
    /**
     * Null when the relation is loaded but empty — a content entry whose
     * author has since been deleted still serialises, with a null author,
     * rather than failing the whole page.
     *
     * @param  class-string<JsonResource>  $resource
     */
    protected function whenLoadedResource(string $relation, string $resource): array|MissingValue|null
    {
        return $this->whenLoaded(
            $relation,
            fn () => (new $resource($this->resource->{$relation}))->resolve(),
        );
    }

    /**
     * @param  class-string<JsonResource>  $resource
     */
    protected function whenLoadedCollection(string $relation, string $resource): array|MissingValue
    {
        return $this->whenLoaded(
            $relation,
            fn () => $resource::collection($this->resource->{$relation})->resolve(),
        );
    }
}
