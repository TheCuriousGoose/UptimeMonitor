<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\RedirectResponse;

class MonitorStateController extends Controller
{
    /**
     * Pause or resume a monitor. Resuming schedules the next check immediately
     * so the dashboard stops showing stale data.
     */
    public function update(Monitor $monitor): RedirectResponse
    {
        $this->authorize('update', $monitor);

        $resuming = ! $monitor->is_active;

        $monitor->forceFill([
            'is_active' => $resuming,
            'next_check_at' => $resuming ? now() : $monitor->next_check_at,
            'failure_streak' => 0,
            'success_streak' => 0,
        ])->save();

        return back()->with('success', __(
            $resuming ? 'monitors.messages.resumed.success' : 'monitors.messages.paused.success',
        ));
    }
}
