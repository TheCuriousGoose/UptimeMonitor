<?php

namespace App\Enums;

enum ChannelType: string
{
    case Email = 'email';
    case Webhook = 'webhook';
    case Slack = 'slack';
    case Discord = 'discord';
    case PagerDuty = 'pagerduty';
    case Opsgenie = 'opsgenie';
    case Teams = 'teams';
    case GoogleChat = 'google_chat';

    /**
     * The config key holding this channel's delivery target.
     */
    public function destinationKey(): string
    {
        return match ($this) {
            self::Email => 'email',
            self::PagerDuty => 'routing_key',
            self::Opsgenie => 'api_key',
            default => 'url',
        };
    }

    /**
     * Whether the destination is a credential rather than an address. These
     * are never sent back to the browser once stored.
     */
    public function destinationIsSecret(): bool
    {
        return match ($this) {
            self::PagerDuty, self::Opsgenie => true,
            default => false,
        };
    }

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
            self::PagerDuty => [
                // Events API v2 integration keys are 32-character hex.
                'config.routing_key' => ['required', 'string', 'size:32', 'alpha_num'],
            ],
            self::Opsgenie => [
                'config.api_key' => ['required', 'string', 'max:255'],
            ],
            self::Webhook, self::Slack, self::Discord, self::Teams, self::GoogleChat => [
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
