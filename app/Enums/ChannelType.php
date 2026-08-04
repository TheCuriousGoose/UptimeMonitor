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

    /**
     * Integrations are alerting products with their own incident lifecycle —
     * a recovery closes the alert the outage opened — rather than a
     * fire-and-forget message sink. They get their own page for that reason.
     */
    public function isIntegration(): bool
    {
        return match ($this) {
            self::PagerDuty, self::Opsgenie, self::Teams => true,
            default => false,
        };
    }

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
            self::Webhook, self::Slack, self::Discord, self::Teams => [
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

    /**
     * Types offered on the notification-channels page.
     *
     * @return array<int, string>
     */
    public static function basicValues(): array
    {
        return array_values(array_map(
            fn (self $type) => $type->value,
            array_filter(self::cases(), fn (self $type) => ! $type->isIntegration()),
        ));
    }

    /**
     * Types offered on the integrations page.
     *
     * @return array<int, string>
     */
    public static function integrationValues(): array
    {
        return array_values(array_map(
            fn (self $type) => $type->value,
            array_filter(self::cases(), fn (self $type) => $type->isIntegration()),
        ));
    }
}
