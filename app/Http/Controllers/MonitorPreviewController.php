<?php

namespace App\Http\Controllers;

use App\Checkers\CheckerRegistry;
use App\Http\Requests\Monitors\PreviewMonitorRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

class MonitorPreviewController extends Controller
{
    /**
     * Run a configured-but-unsaved check once and report what happened.
     *
     * Without this the only way to find out a URL has a typo or an auth header
     * is wrong is to save the monitor and wait out an interval. The check runs
     * through the same checker and the same outbound guards as a scheduled
     * one, against a Monitor that is never persisted — so nothing is recorded,
     * no incident opens, and nobody is notified.
     */
    public function store(PreviewMonitorRequest $request, CheckerRegistry $checkers): JsonResponse
    {
        $monitor = $request->toMonitor();

        try {
            $result = $checkers->resolve($monitor->type->value)->check($monitor);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'is_up' => false,
                'response_ms' => 0,
                'error' => __('monitors.preview.failed'),
            ]);
        }

        return response()->json([
            'is_up' => $result->isUp,
            'response_ms' => $result->responseMs,
            'error' => $result->error,
            'status_code' => $result->meta['status_code'] ?? null,
        ]);
    }
}
