<?php

namespace App\Enums;

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
}
