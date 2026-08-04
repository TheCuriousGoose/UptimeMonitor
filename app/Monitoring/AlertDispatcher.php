<?php

namespace App\Monitoring;

use App\Jobs\SendAlert;
use App\Models\Monitor;

class AlertDispatcher
{
    public function dispatch(Monitor $monitor, AlertMessage $message): void
    {
        $channels = $monitor->relationLoaded('notificationChannels')
            ? $monitor->notificationChannels->where('is_active', true)
            : $monitor->notificationChannels()->active()->get();

        foreach ($channels as $channel) {
            SendAlert::dispatch($channel, $message);
        }
    }
}
