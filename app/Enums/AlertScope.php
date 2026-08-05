<?php

namespace App\Enums;

enum AlertScope: string
{
    /**
     * Alerts on every monitor its owner has, including ones created after the
     * channel. This is the default so a new monitor is never silently
     * unmonitored just because nobody remembered to attach it.
     */
    case All = 'all';

    /**
     * Alerts only on the monitors in the pivot.
     */
    case Selected = 'selected';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
