<?php

use App\Console\Commands\DispatchDueChecks;
use App\Console\Commands\PruneMonitorChecks;
use Illuminate\Support\Facades\Schedule;

// Twice a minute so monitors on a 30 second interval stay on schedule.
Schedule::command(DispatchDueChecks::class)->everyThirtySeconds()->withoutOverlapping();

Schedule::command(PruneMonitorChecks::class)->dailyAt('03:15');
