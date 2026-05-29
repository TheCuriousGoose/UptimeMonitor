<?php

namespace App\Checkers;

use App\Models\Monitor;
use Illuminate\Support\Facades\Http;
use Throwable;

class HttpChecker implements Checker
{
    public function check(Monitor $monitor): CheckResult
    {
        $start = hrtime(true);

        try {
            $response = Http::timeout($monitor->timeout)->get($monitor->url);
            $ms = (int) ((hrtime(true) - $start) / 1_000_000);

            return $response->successful()
                ? CheckResult::up($ms, ['status_code' => $response->status()])
                : CheckResult::down("HTTP {$response->status()}", $ms, ['status_code' => $response->status()]);
        } catch (Throwable $e) {
            $ms = (int) ((hrtime(true) - $start) / 1_000_000);

            return CheckResult::down($e->getMessage(), $ms);
        }
    }

    public function queue(): string
    {
        return 'checks-http';
    }
}
