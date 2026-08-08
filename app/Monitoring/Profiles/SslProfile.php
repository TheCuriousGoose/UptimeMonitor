<?php

namespace App\Monitoring\Profiles;

use App\Checkers\SslChecker;

class SslProfile implements MonitorProfile
{
    /**
     * The target is stored as a URL even though only the host and port are
     * used, so an owner can paste the same address they monitor over HTTP.
     */
    public function expectsUrl(): bool
    {
        return true;
    }

    public function checker(): string
    {
        return SslChecker::class;
    }

    public function rules(): array
    {
        return [
            'config.warn_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function defaults(): array
    {
        return ['warn_days' => 14];
    }

    public function casts(): array
    {
        return ['warn_days' => ConfigCast::Int];
    }
}
