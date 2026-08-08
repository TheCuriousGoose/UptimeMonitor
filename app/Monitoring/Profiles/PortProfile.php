<?php

namespace App\Monitoring\Profiles;

use App\Checkers\PortChecker;

class PortProfile implements MonitorProfile
{
    public function expectsUrl(): bool
    {
        return false;
    }

    public function checker(): string
    {
        return PortChecker::class;
    }

    public function rules(): array
    {
        return [
            'config.port' => ['required', 'integer', 'min:1', 'max:65535'],
        ];
    }

    public function defaults(): array
    {
        return ['port' => 443];
    }

    public function casts(): array
    {
        return ['port' => ConfigCast::Int];
    }
}
