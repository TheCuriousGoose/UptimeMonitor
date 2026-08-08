<?php

namespace App\Enums;

use App\Checkers\Checker;
use App\Monitoring\Profiles\ConfigCast;
use App\Monitoring\Profiles\DnsProfile;
use App\Monitoring\Profiles\HttpProfile;
use App\Monitoring\Profiles\KeywordProfile;
use App\Monitoring\Profiles\MonitorProfile;
use App\Monitoring\Profiles\PingProfile;
use App\Monitoring\Profiles\PortProfile;
use App\Monitoring\Profiles\SslProfile;

enum MonitorType: string
{
    case Http = 'http';
    case Keyword = 'keyword';
    case Port = 'port';
    case Ping = 'ping';
    case Dns = 'dns';
    case Ssl = 'ssl';

    public const METHODS = HttpProfile::METHODS;

    public const CONTENT_TYPES = HttpProfile::CONTENT_TYPES;

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
     * Everything that varies by type — rules, defaults, casts, checker.
     */
    public function profile(): MonitorProfile
    {
        return match ($this) {
            self::Http => new HttpProfile,
            self::Keyword => new KeywordProfile,
            self::Port => new PortProfile,
            self::Ping => new PingProfile,
            self::Dns => new DnsProfile,
            self::Ssl => new SslProfile,
        };
    }

    /**
     * Whether the monitor target is a full URL rather than a bare hostname.
     */
    public function expectsUrl(): bool
    {
        return $this->profile()->expectsUrl();
    }

    /**
     * Validation rules for the type specific `config` payload.
     *
     * @return array<string, array<int, mixed>>
     */
    public function configRules(): array
    {
        return $this->profile()->rules();
    }

    /**
     * Values applied when the user leaves a config field untouched.
     *
     * @return array<string, mixed>
     */
    public function defaultConfig(): array
    {
        return $this->profile()->defaults();
    }

    /**
     * @return array<string, ConfigCast>
     */
    public function configCasts(): array
    {
        return $this->profile()->casts();
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The vocabulary the monitor form needs to render its type-specific
     * fields: which types take a URL, and the closed lists behind each select.
     *
     * Sent to the browser rather than restated there. MonitorForm.vue and the
     * onboarding wizard each kept their own copy of these arrays, so a value
     * added to a profile's rules was accepted by the API but never offered on
     * the form — and one removed stayed on the form and failed validation.
     *
     * @return array<string, array<int, string>>
     */
    public static function formOptions(): array
    {
        return [
            'url_types' => array_values(array_map(
                fn (self $type) => $type->value,
                array_filter(self::cases(), fn (self $type) => $type->expectsUrl()),
            )),
            'methods' => HttpProfile::METHODS,
            'content_types' => HttpProfile::CONTENT_TYPES,
            'auth_types' => HttpProfile::AUTH_TYPES,
            'record_types' => DnsProfile::RECORD_TYPES,
        ];
    }

    /**
     * The checker class for each type, keyed by value — the map the
     * CheckerRegistry is built from.
     *
     * @return array<string, class-string<Checker>>
     */
    public static function checkerMap(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $map, self $type) => $map + [$type->value => $type->profile()->checker()],
            [],
        );
    }
}
