<?php

namespace App\Enums;

enum ChannelType: string
{
    case Email = 'email';
    case Webhook = 'webhook';
    case Slack = 'slack';
    case Discord = 'discord';

    /**
     * Validation rules for this channel type's `config` payload.
     *
     * @return array<string, array<int, mixed>>
     */
    public function configRules(): array
    {
        return match ($this) {
            self::Email => [
                'config.email' => ['required', 'email', 'max:255'],
            ],
            self::Webhook, self::Slack, self::Discord => [
                'config.url' => ['required', 'url', 'max:2048'],
            ],
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
