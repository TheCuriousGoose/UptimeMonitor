<?php

namespace App\Enums;

enum MonitorStatus: string
{
    case Up = 'up';
    case Down = 'down';
    /** Responding, but slower than the monitor's threshold. */
    case Degraded = 'degraded';
    /** Inside a scheduled maintenance window. */
    case Maintenance = 'maintenance';
    case Paused = 'paused';
    /** Created but not yet checked. */
    case Pending = 'pending';
}
