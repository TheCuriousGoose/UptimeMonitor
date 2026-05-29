<?php

namespace App\Console\Commands;

use App\Checkers\CheckerRegistry;
use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DispatchDueChecks extends Command
{
    protected $signature = 'monitors:dispatch';

    protected $description = 'Dispatch check jobs for all monitors that are due';

    public function handle(CheckerRegistry $registry): void
    {
        $lock = Cache::lock('monitors:dispatch', 55);

        if (! $lock->get()) {
            $this->warn('Another dispatch is already running. Skipping.');

            return;
        }

        try {
            Monitor::query()
                ->where('is_active', true)
                ->where('next_check_at', '<=', now())
                ->select(['id', 'type', 'check_interval', 'next_check_at'])
                ->chunkById(500, function ($monitors) use ($registry): void {
                    foreach ($monitors as $monitor) {
                        $queue = $registry->resolve($monitor->type->value)->queue();

                        dispatch(new RunMonitorCheck($monitor))->onQueue($queue);
                    }
                });
        } finally {
            $lock->release();
        }
    }
}
