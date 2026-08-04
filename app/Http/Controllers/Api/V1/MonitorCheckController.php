<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use Illuminate\Http\Response;

class MonitorCheckController extends Controller
{
    /**
     * Run a check right now instead of waiting for the next scheduled slot.
     * Dispatched asynchronously, so this reports "accepted" rather than the
     * check's outcome — poll GET /monitors/{monitor}/checks for the result.
     */
    public function store(Monitor $monitor)
    {
        $this->authorize('view', $monitor);

        RunMonitorCheck::dispatch($monitor);

        return response()->json([
            'message' => __('monitors.messages.check_queued.success'),
        ], Response::HTTP_ACCEPTED);
    }
}
