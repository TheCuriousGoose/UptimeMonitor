<?php

namespace App\Monitoring\Profiles;

/**
 * How a submitted config value is coerced before it is stored.
 *
 * The config cast serialises whatever it is handed, so a checkbox posting
 * "0" would be stored as the string "0" — which is truthy everywhere it gets
 * read. Declaring the coercion next to the key's default and rules is what
 * keeps a new config field from silently landing here as a string.
 */
enum ConfigCast
{
    case Bool;
    case Int;
    case NullableInt;
    case NullableString;
    case Arr;
    /** Stored as submitted. */
    case Raw;

    public function apply(mixed $value): mixed
    {
        return match ($this) {
            self::Bool => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::Int => (int) $value,
            self::NullableInt => ($value === null || $value === '') ? null : (int) $value,
            self::NullableString => ($value === null || $value === '') ? null : (string) $value,
            self::Arr => is_array($value) ? $value : [],
            self::Raw => $value,
        };
    }
}
