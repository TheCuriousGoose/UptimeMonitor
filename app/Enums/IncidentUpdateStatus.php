<?php

namespace App\Enums;

/**
 * The stage a public update reports. Null on a private note — that is what
 * separates an internal comment from a status post.
 */
enum IncidentUpdateStatus: string
{
    case Investigating = 'investigating';
    case Identified = 'identified';
    case Monitoring = 'monitoring';
    case Resolved = 'resolved';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
