<?php

namespace App\Http\Requests\Concerns;

/**
 * Handling for the multi-select fields that attach one record to another.
 *
 * Every one of these forms submits its list with a blank entry when nothing
 * is ticked — otherwise the key is absent and "clear the selection" is
 * indistinguishable from "leave it alone". That blank has to be dropped before
 * an exists rule sees it, and again before the uuids reach a query. Both
 * halves were restated in each request.
 */
trait NormalisesUuidLists
{
    /**
     * Drop the blank placeholder from a submitted list, in place, so
     * validation never sees it.
     */
    protected function pruneUuidList(string $key): void
    {
        if (! $this->has($key)) {
            return;
        }

        $this->merge([$key => self::onlyUuids($this->input($key))]);
    }

    /**
     * The validated uuids for a list field.
     *
     * @return array<int, string>
     */
    protected function uuidList(string $key, bool $validated = true): array
    {
        return self::onlyUuids(
            $validated ? $this->safe()->input($key, []) : $this->input($key, []),
        );
    }

    /**
     * @return array<int, string>
     */
    private static function onlyUuids(mixed $value): array
    {
        return array_values(array_filter(
            (array) $value,
            fn ($uuid) => is_string($uuid) && $uuid !== '',
        ));
    }
}
