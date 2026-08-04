<?php

namespace App\Enums;

use Illuminate\Validation\Rule;

enum MonitorType: string
{
    case Http = 'http';
    case Keyword = 'keyword';
    case Port = 'port';
    case Ping = 'ping';
    case Dns = 'dns';
    case Ssl = 'ssl';

    /**
     * Whether the monitor target is a full URL rather than a bare hostname.
     */
    public function expectsUrl(): bool
    {
        return match ($this) {
            self::Http, self::Keyword, self::Ssl => true,
            self::Port, self::Ping, self::Dns => false,
        };
    }

    /**
     * Validation rules for the type specific `config` payload.
     *
     * @return array<string, array<int, mixed>>
     */
    public function configRules(): array
    {
        $httpRules = [
            'config.method' => ['sometimes', 'string', Rule::in(['GET', 'POST', 'HEAD'])],
            'config.expected_status' => ['sometimes', 'nullable', 'integer', 'min:100', 'max:599'],
            'config.verify_ssl' => ['sometimes', 'boolean'],
        ];

        return match ($this) {
            self::Http => $httpRules,
            self::Keyword => $httpRules + [
                'config.keyword' => ['required', 'string', 'max:255'],
                'config.invert' => ['sometimes', 'boolean'],
            ],
            self::Port => [
                'config.port' => ['required', 'integer', 'min:1', 'max:65535'],
            ],
            self::Dns => [
                'config.record_type' => ['required', 'string', Rule::in(['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS'])],
                'config.expected' => ['sometimes', 'nullable', 'string', 'max:255'],
            ],
            self::Ssl => [
                'config.warn_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            ],
            self::Ping => [],
        };
    }

    /**
     * Values applied when the user leaves a config field untouched.
     *
     * @return array<string, mixed>
     */
    public function defaultConfig(): array
    {
        return match ($this) {
            self::Http => ['method' => 'GET', 'expected_status' => null, 'verify_ssl' => true],
            self::Keyword => ['method' => 'GET', 'expected_status' => null, 'verify_ssl' => true, 'keyword' => '', 'invert' => false],
            self::Port => ['port' => 443],
            self::Dns => ['record_type' => 'A', 'expected' => null],
            self::Ssl => ['warn_days' => 14],
            self::Ping => [],
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
