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
     * Whether this event is about latency rather than availability.
     *
     * Incident-tracking notifiers key their dedup on this: a degradation and
     * an outage are separate subjects, so they must not collapse onto one
     * alert. Sharing a key meant a Down deduplicated into an open "slow"
     * alert instead of escalating, and the Improved that followed closed it.
     */
    public function isLatency(): bool
    {
        return match ($this) {
            self::Degraded, self::Improved => true,
            self::Down, self::Recovered, self::Reminder => false,
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

    /**
     * The accent colour for chat surfaces, as `#rrggbb`.
     *
     * Three-way, not two: every chat notifier used to derive this from
     * `=== Down`, which painted a still-down reminder and a degradation in
     * the same green as a recovery.
     */
    public function color(): string
    {
        return match ($this->severity()) {
            'error' => '#dc2626',
            'warning' => '#f59e0b',
            default => '#16a34a',
        };
    }

    /**
     * Short human label for surfaces that show status as a field.
     */
    public function label(): string
    {
        return match ($this) {
            self::Down => 'Down',
            self::Recovered => 'Recovered',
            self::Reminder => 'Still down',
            self::Degraded => 'Degraded',
            self::Improved => 'Improved',
        };
    }
}
