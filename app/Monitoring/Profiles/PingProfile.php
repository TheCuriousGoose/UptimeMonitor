<?php

namespace App\Monitoring\Profiles;

use App\Checkers\PingChecker;

class PingProfile implements MonitorProfile
{
    public function expectsUrl(): bool
    {
        return false;
    }

    public function checker(): string
    {
        return PingChecker::class;
    }

    public function rules(): array
    {
        return [];
    }

    public function defaults(): array
    {
        return [];
    }

    public function casts(): array
    {
        return [];
    }
}
