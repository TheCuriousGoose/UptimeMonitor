<?php

namespace App\Monitoring;

enum AlertEvent: string
{
    case Down = 'down';
    case Recovered = 'recovered';
    case Degraded = 'degraded';
    case Improved = 'improved';
    /** A still-open outage, repeated on the channel's own cadence. */
    case Reminder = 'reminder';

    /**
     * Whether this event closes something rather than opening it.
     *
     * Notifiers branch on this instead of comparing against Down, which is
     * what let PagerDuty resolve a live page the moment a degraded alert
     * arrived for an outage that was still ongoing.
     */
    public function isResolution(): bool
    {
        return match ($this) {
            self::Recovered, self::Improved => true,
            self::Down, self::Degraded, self::Reminder => false,
        };
    }

    /**
     * Maps onto the severity vocabulary PagerDuty and Opsgenie share.
     */
    public function severity(): string
    {
        return match ($this) {
            self::Down, self::Reminder => 'error',
            self::Degraded => 'warning',
            self::Recovered, self::Improved => 'info',
        };
    }
}
