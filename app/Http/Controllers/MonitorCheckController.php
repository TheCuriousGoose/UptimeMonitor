<?php

namespace App\Http\Controllers;

use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use Illuminate\Http\RedirectResponse;

class MonitorCheckController extends Controller
{
    /**
     * Run a check right now instead of waiting for the next scheduled slot.
     */
    public function store(Monitor $monitor): RedirectResponse
    {
        $this->authorize('view', $monitor);

        RunMonitorCheck::dispatch($monitor);

        return back()->with('success', __('monitors.messages.check_queued.success'));
    }
}
