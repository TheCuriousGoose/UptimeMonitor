<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;

class MonitorStateController extends Controller
{
    /**
     * Pause or resume a monitor. Resuming schedules the next check
     * immediately, same as the web action.
     */
    public function update(Monitor $monitor)
    {
        $this->authorize('update', $monitor);

        $resuming = ! $monitor->is_active;

        $monitor->forceFill([
            'is_active' => $resuming,
            'next_check_at' => $resuming ? now() : $monitor->next_check_at,
            'failure_streak' => 0,
            'success_streak' => 0,
        ])->save();

        return new MonitorResource($monitor->fresh());
    }
}
