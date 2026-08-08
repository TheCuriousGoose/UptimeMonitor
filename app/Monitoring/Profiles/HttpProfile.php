<?php

namespace App\Monitoring\Profiles;

use App\Checkers\HttpChecker;
use App\Rules\HttpHeaderValue;
use Illuminate\Validation\Rule;

class HttpProfile implements MonitorProfile
{
    public const METHODS = ['GET', 'POST', 'HEAD', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    public const CONTENT_TYPES = [
        'application/json',
        'application/x-www-form-urlencoded',
        'text/plain',
        'application/xml',
    ];

    public const AUTH_TYPES = ['none', 'basic', 'bearer'];

    public function expectsUrl(): bool
    {
        return true;
    }

    public function checker(): string
    {
        return HttpChecker::class;
    }

    public function rules(): array
    {
        return [
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
            'config.auth_type' => ['sometimes', 'string', Rule::in(self::AUTH_TYPES)],
            'config.auth_username' => ['sometimes', 'nullable', 'string', 'max:255', 'required_if:config.auth_type,basic'],
            'config.auth_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'config.auth_token' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'config.follow_redirects' => ['sometimes', 'boolean'],
            'config.max_redirects' => ['sometimes', 'integer', 'min:0', 'max:10'],
        ];
    }

    public function defaults(): array
    {
        return [
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
    }

    public function casts(): array
    {
        return [
            'method' => ConfigCast::Raw,
            'expected_status' => ConfigCast::NullableInt,
            'expected_status_codes' => ConfigCast::Arr,
            'verify_ssl' => ConfigCast::Bool,
            'headers' => ConfigCast::Arr,
            'body' => ConfigCast::NullableString,
            'content_type' => ConfigCast::NullableString,
            'auth_type' => ConfigCast::Raw,
            'auth_username' => ConfigCast::NullableString,
            'auth_password' => ConfigCast::NullableString,
            'auth_token' => ConfigCast::NullableString,
            'follow_redirects' => ConfigCast::Bool,
            'max_redirects' => ConfigCast::Int,
        ];
    }
}
