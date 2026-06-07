<?php

namespace App\Checkers;

use App\Models\Monitor;

interface Checker
{
    public function check(Monitor $monitor): CheckResult;

    public function queue(): string;
}
