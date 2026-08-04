<?php

namespace App\Monitoring;

/**
 * Decides which queue monitoring work lands on.
 *
 * By default everything shares one queue, so a single worker runs the whole
 * app. Setting MONITORING_SEPARATE_QUEUES=true splits work into per-lane
 * queues for installs large enough to want checks isolated from alerts.
 */
class QueueResolver
{
    public const LANE_ALERTS = 'alerts';

    public function for(string $lane): string
    {
        if (! config('monitoring.separate_queues', false)) {
            return (string) config('monitoring.queue', 'default');
        }

        return $lane;
    }

    /**
     * Every queue this app may dispatch to, highest priority first.
     *
     * @return array<int, string>
     */
    public function all(): array
    {
        return array_values(array_unique([
            (string) config('monitoring.queue', 'default'),
            self::LANE_ALERTS,
            'checks-http',
            'checks-network',
        ]));
    }
}
