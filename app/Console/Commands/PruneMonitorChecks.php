<?php

namespace App\Console\Commands;

use App\Models\MonitorCheck;
use Illuminate\Console\Command;

/**
 * Individual check rows grow without bound; resolved incidents keep the
 * long term history, so raw checks only need a rolling window.
 */
class PruneMonitorChecks extends Command
{
    protected $signature = 'monitors:prune {--days= : Retain checks newer than this many days}';

    protected $description = 'Delete monitor checks older than the retention window';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('monitoring.retention_days', 90));

        if ($days < 1) {
            $this->error('Retention must be at least one day.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $deleted = 0;

        do {
            $batch = MonitorCheck::query()->where('checked_at', '<', $cutoff)->limit(5000)->delete();
            $deleted += $batch;
        } while ($batch > 0);

        $this->info("Pruned {$deleted} check(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
