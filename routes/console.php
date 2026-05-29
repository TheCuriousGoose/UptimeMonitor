<?php

use App\Console\Commands\DispatchDueChecks;
use Illuminate\Support\Facades\Schedule;

Schedule::command(DispatchDueChecks::class)->everyMinute()->withoutOverlapping();
