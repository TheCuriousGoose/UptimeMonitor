<?php

use App\Console\Commands\DispatchDueChecks;
use App\Console\Commands\PruneMonitorChecks;
use App\Console\Commands\SweepAlerts;
use Illuminate\Support\Facades\Schedule;

Schedule::command(DispatchDueChecks::class)->everyThirtySeconds()->withoutOverlapping();
Schedule::command(SweepAlerts::class)->everyMinute()->withoutOverlapping();

Schedule::command(PruneMonitorChecks::class)->dailyAt('03:15');
