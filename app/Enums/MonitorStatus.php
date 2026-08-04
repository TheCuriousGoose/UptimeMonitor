<?php

namespace App\Enums;

enum MonitorStatus: string
{
    case Up = 'up';
    case Down = 'down';
    case Paused = 'paused';
    /** Created but not yet checked. */
    case Pending = 'pending';
}
