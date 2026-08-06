<?php

namespace App\Enums;

use App\Rules\HttpHeaderValue;
use Illuminate\Validation\Rule;

enum MonitorType: string
{
    case Http = 'http';
    case Keyword = 'keyword';
    case Port = 'port';
    case Ping = 'ping';
    case Dns = 'dns';
    case Ssl = 'ssl';

    public const METHODS = ['GET', 'POST', 'HEAD', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    public const CONTENT_TYPES = [
        'application/json',
        'application/x-www-form-urlencoded',
        'text/plain',
        'application/xml',
    ];

    /**
     * Config keys whose value is a credential. Masked on the way out and
     * merged back from storage when the mask is posted unchanged.
     */
    public const SECRET_KEYS = ['auth_password', 'auth_token'];

    /**
     * Header names whose value is a credential rather than a routing hint.
     */
    public const SECRET_HEADERS = ['authorization', 'cookie', 'x-api-key', 'proxy-authorization'];

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
            'config.method' => ['sometimes', 'string', Rule::in(self::METHODS)],
            // Superseded by expected_status_codes but still validated: an API
            // client that has not been updated still posts it.
            'config.expected_status' => ['sometimes', 'nullable', 'integer', 'min:100', 'max:599'],
            'config.expected_status_codes' => ['sometimes', 'array', 'max:10'],
            // An exact code, an inclusive range, or a class — see StatusMatcher.
            'config.expected_status_codes.*' => ['string', 'regex:/^([1-5]\d{2}(-[1-5]\d{2})?|[1-5]xx)$/i'],
            'config.verify_ssl' => ['sometimes', 'boolean'],
            'config.headers' => ['sometimes', 'array', 'max:20'],
            'config.headers.*' => ['string', 'max:2048', new HttpHeaderValue],
            'config.body' => ['sometimes', 'nullable', 'string', 'max:8192'],
            'config.content_type' => ['sometimes', 'nullable', 'string', Rule::in(self::CONTENT_TYPES)],
            'config.auth_type' => ['sometimes', 'string', Rule::in(['none', 'basic', 'bearer'])],
            'config.auth_username' => ['sometimes', 'nullable', 'string', 'max:255', 'required_if:config.auth_type,basic'],
            'config.auth_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'config.auth_token' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'config.follow_redirects' => ['sometimes', 'boolean'],
            'config.max_redirects' => ['sometimes', 'integer', 'min:0', 'max:10'],
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
        // MonitorRequest intersects the submitted config against these keys,
        // so anything missing here is silently discarded on save.
        $http = [
            'method' => 'GET',
            'expected_status' => null,
            'expected_status_codes' => [],
            'verify_ssl' => true,
            'headers' => [],
            'body' => null,
            'content_type' => null,
            'auth_type' => 'none',
            'auth_username' => null,
            'auth_password' => null,
            'auth_token' => null,
            'follow_redirects' => true,
            'max_redirects' => 5,
        ];

        return match ($this) {
            self::Http => $http,
            self::Keyword => $http + ['keyword' => '', 'invert' => false],
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
