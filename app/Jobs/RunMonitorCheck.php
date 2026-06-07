<?php

namespace App\Jobs;

use App\Checkers\CheckerRegistry;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use Cron\CronExpression;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunMonitorCheck implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly Monitor $monitor) {}

    public function handle(CheckerRegistry $registry): void
    {
        $checker = $registry->resolve($this->monitor->type->value);
        $result = $checker->check($this->monitor);

        MonitorCheck::create([
            'monitor_id' => $this->monitor->id,
            'is_up' => $result->isUp,
            'response_ms' => $result->responseMs,
            'error' => $result->error,
            'meta' => $result->meta ?: null,
            'checked_at' => now(),
        ]);

        $this->monitor->update([
            'latest_is_up' => $result->isUp,
            'next_check_at' => (new CronExpression($this->monitor->check_interval))
                ->getNextRunDate(),
        ]);
    }
}
