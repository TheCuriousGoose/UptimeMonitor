<?php

namespace App\Enums;

use App\Monitoring\Notifiers\DiscordNotifier;
use App\Monitoring\Notifiers\GoogleChatNotifier;
use App\Monitoring\Notifiers\MailNotifier;
use App\Monitoring\Notifiers\Notifier;
use App\Monitoring\Notifiers\OpsgenieNotifier;
use App\Monitoring\Notifiers\PagerDutyNotifier;
use App\Monitoring\Notifiers\SlackNotifier;
use App\Monitoring\Notifiers\TeamsNotifier;
use App\Monitoring\Notifiers\WebhookNotifier;

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
     * The notifier that delivers this channel's alerts.
     *
     * @return class-string<Notifier>
     */
    public function notifier(): string
    {
        return match ($this) {
            self::Email => MailNotifier::class,
            self::Webhook => WebhookNotifier::class,
            self::Slack => SlackNotifier::class,
            self::Discord => DiscordNotifier::class,
            self::PagerDuty => PagerDutyNotifier::class,
            self::Opsgenie => OpsgenieNotifier::class,
            self::Teams => TeamsNotifier::class,
            self::GoogleChat => GoogleChatNotifier::class,
        };
    }

    /**
     * The map the NotifierRegistry is built from. Derived from the cases so a
     * new channel type cannot be offered on the form but left undeliverable.
     *
     * @return array<string, class-string<Notifier>>
     */
    public static function notifierMap(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $map, self $type) => $map + [$type->value => $type->notifier()],
            [],
        );
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
