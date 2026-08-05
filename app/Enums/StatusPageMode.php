<?php

namespace App\Enums;

/**
 * Which colour scheme a public status page renders in.
 *
 * This is the page owner's choice, not the visitor's: a status page carries
 * the owner's house style, so it must not inherit whatever appearance the
 * visitor happens to have stored from using the app itself.
 */
enum StatusPageMode: string
{
    case Light = 'light';
    case Dark = 'dark';

    /**
     * Follows the visitor's OS preference. Rendered as a `prefers-color-scheme`
     * media query rather than a class toggle, so it survives server-side
     * rendering without a flash of the wrong palette.
     */
    case System = 'system';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
