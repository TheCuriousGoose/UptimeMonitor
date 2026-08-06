<?php

namespace App\Console\Commands;

use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use App\Support\SqlDialect;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
            while (true) {
                $monitors = Monitor::query()->due()->orderBy('id')->limit(500)->get();

                if ($monitors->isEmpty()) {
                    break;
                }

                // Claim the whole batch in one statement rather than a save()
                // per row. One short lock, taken in primary-key order, instead
                // of hundreds interleaved with the workers' own writes — the
                // row-at-a-time version deadlocked against StatusEvaluator and
                // those checks were lost.
                Monitor::query()
                    ->whereIn('id', $monitors->modelKeys())
                    ->update([
                        'next_check_at' => DB::raw(
                            SqlDialect::nowPlusSeconds('interval_seconds', 30),
                        ),
                    ]);

                foreach ($monitors as $monitor) {
                    RunMonitorCheck::dispatch($monitor);
                    $dispatched++;
                }
            }
        } finally {
            $lock->release();
        }

        $this->info("Dispatched {$dispatched} check(s).");

        return self::SUCCESS;
    }
}
