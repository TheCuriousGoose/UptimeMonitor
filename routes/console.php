<?php

use App\Console\Commands\DispatchDueChecks;
use App\Console\Commands\PruneMonitorChecks;
use App\Console\Commands\SweepAlerts;
use Illuminate\Support\Facades\Schedule;

// Twice a minute so monitors on a 30 second interval stay on schedule.
Schedule::command(DispatchDueChecks::class)->everyThirtySeconds()->withoutOverlapping();

// Its own schedule entry rather than folded into the dispatcher: a slow
// notifier must never delay the next round of checks.
Schedule::command(SweepAlerts::class)->everyMinute()->withoutOverlapping();

Schedule::command(PruneMonitorChecks::class)->dailyAt('03:15');
