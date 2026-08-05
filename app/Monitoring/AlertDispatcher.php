<?php

namespace App\Monitoring;

use App\Jobs\SendAlert;
use App\Models\Monitor;
use App\Models\NotificationChannel;

class AlertDispatcher
{
    public function dispatch(Monitor $monitor, AlertMessage $message): void
    {
        // Always queried, never read off a preloaded relation: a channel scoped
        // to every monitor is by definition absent from the pivot, so
        // $monitor->notificationChannels is no longer the complete answer.
        $channels = NotificationChannel::query()
            ->active()
            ->forMonitor($monitor)
            ->get();

        foreach ($channels as $channel) {
            SendAlert::dispatch($channel, $message);
        }
    }
}
