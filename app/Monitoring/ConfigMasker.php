<?php

namespace App\Monitoring;

use App\Enums\MonitorType;

/**
 * Hides credentials in a monitor's config on the way out, and puts them back
 * on the way in.
 *
 * The write half is not optional. MonitorResource returns the whole resolved
 * config, so without it a user who opens a monitor to rename it posts the
 * mask back and saves the literal string "••••••••" as their API token.
 */
final class ConfigMasker
{
    public const MASK = '********';

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function mask(array $config): array
    {
        foreach (MonitorType::SECRET_KEYS as $key) {
            if (self::isSet($config[$key] ?? null)) {
                $config[$key] = self::MASK;
            }
        }

        foreach ((array) ($config['headers'] ?? []) as $name => $value) {
            if (self::isSecretHeader((string) $name) && self::isSet($value)) {
                $config['headers'][$name] = self::MASK;
            }
        }

        return $config;
    }

    /**
     * Merge stored secrets back over any value that came back as the mask.
     *
     * @param  array<string, mixed>  $submitted
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    public static function unmask(array $submitted, array $stored): array
    {
        foreach (MonitorType::SECRET_KEYS as $key) {
            if (($submitted[$key] ?? null) === self::MASK) {
                $submitted[$key] = $stored[$key] ?? null;
            }
        }

        foreach ((array) ($submitted['headers'] ?? []) as $name => $value) {
            if ($value === self::MASK) {
                $submitted['headers'][$name] = $stored['headers'][$name] ?? null;
            }
        }

        return $submitted;
    }

    private static function isSecretHeader(string $name): bool
    {
        return in_array(strtolower($name), MonitorType::SECRET_HEADERS, true);
    }

    private static function isSet(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }
}
