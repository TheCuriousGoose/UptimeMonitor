<?php

namespace App\Enums;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

enum SettingType: string
{
    case String = 'string';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Float = 'float';
    case Json = 'json';
    case Secret = 'secret';

    /** Stored encrypted and never sent to the browser in clear. */
    public function isSecret(): bool
    {
        return $this === self::Secret;
    }

    /**
     * The stored string as the type this setting declares.
     *
     * Paired with {@see serialize()} on the enum rather than split across the
     * repository, so a new case cannot be readable but unwritable — PHP's
     * exhaustive match makes adding one a compile-time prompt for both halves.
     */
    public function cast(?string $stored): mixed
    {
        return match ($this) {
            self::Boolean => filter_var($stored, FILTER_VALIDATE_BOOLEAN),
            self::Integer => (int) $stored,
            self::Float => (float) $stored,
            self::Json => json_decode((string) $stored, true),
            self::String => (string) ($stored ?? ''),
            self::Secret => self::decrypt($stored),
        };
    }

    public function serialize(mixed $value): string
    {
        return match ($this) {
            self::Boolean => $value ? '1' : '0',
            self::Json => (string) json_encode($value),
            self::Secret => Crypt::encryptString((string) $value),
            self::String, self::Integer, self::Float => (string) $value,
        };
    }

    /**
     * Tolerates a plaintext value so a secret seeded or edited outside the
     * app does not hard-fail the whole settings screen.
     */
    private static function decrypt(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }
}
