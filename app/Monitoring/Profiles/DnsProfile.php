<?php

namespace App\Monitoring\Profiles;

use App\Checkers\DnsChecker;
use Illuminate\Validation\Rule;

class DnsProfile implements MonitorProfile
{
    public const RECORD_TYPES = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS'];

    public function expectsUrl(): bool
    {
        return false;
    }

    public function checker(): string
    {
        return DnsChecker::class;
    }

    public function rules(): array
    {
        return [
            'config.record_type' => ['required', 'string', Rule::in(self::RECORD_TYPES)],
            'config.expected' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function defaults(): array
    {
        return ['record_type' => 'A', 'expected' => null];
    }

    public function casts(): array
    {
        return [
            'record_type' => ConfigCast::Raw,
            'expected' => ConfigCast::NullableString,
        ];
    }
}
