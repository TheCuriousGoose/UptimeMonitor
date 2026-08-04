<?php

namespace App\Console\Commands;

use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DispatchDueChecks extends Command
{
    protected $signature = 'monitors:dispatch';

    protected $description = 'Dispatch check jobs for all monitors that are due';

    public function handle(): int
    {
        $lock = Cache::lock('monitors:dispatch', 55);

        if (! $lock->get()) {
            $this->warn('Another dispatch is already running. Skipping.');

            return self::SUCCESS;
        }

        $dispatched = 0;

        try {
            Monitor::query()->due()->chunkById(500, function ($monitors) use (&$dispatched): void {
                foreach ($monitors as $monitor) {
                    // Push the next check forward immediately so a backed up queue
                    // cannot dispatch the same monitor again on the next tick.
                    $monitor->forceFill(['next_check_at' => $monitor->nextCheckFrom()])->save();

                    RunMonitorCheck::dispatch($monitor);
                    $dispatched++;
                }
            });
        } finally {
            $lock->release();
        }

        $this->info("Dispatched {$dispatched} check(s).");

        return self::SUCCESS;
    }
}
