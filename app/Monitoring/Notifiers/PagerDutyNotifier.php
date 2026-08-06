<?php

namespace App\Monitoring\Notifiers;

use App\Models\NotificationChannel;
use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;
use Illuminate\Support\Facades\Http;

/**
 * PagerDuty Events API v2.
 *
 * The monitor uuid is used as the dedup_key so a recovery resolves the very
 * incident the outage opened, instead of leaving a stale page open forever.
 */
class PagerDutyNotifier implements Notifier
{
    private const ENDPOINT = 'https://events.pagerduty.com/v2/enqueue';

    public function send(NotificationChannel $channel, AlertMessage $message, RenderedAlert $text): void
    {
        $routingKey = $channel->destination();

        if ($routingKey === '') {
            return;
        }

        // Branching on isResolution() rather than comparing against Down:
        // with an identity check, a "degraded" alert resolved the live page
        // for an outage that was still ongoing.
        $resolving = $message->event->isResolution();

        $payload = [
            'routing_key' => $routingKey,
            'event_action' => $resolving ? 'resolve' : 'trigger',
            'dedup_key' => 'monitor-'.$message->monitor->uuid,
        ];

        // PagerDuty rejects a resolve that carries a payload block.
        if (! $resolving) {
            $payload['payload'] = [
                'summary' => $text->title,
                'source' => $message->monitor->url,
                'severity' => $message->event->severity(),
                'timestamp' => $message->occurredAt->toIso8601String(),
                'custom_details' => [
                    'message' => $text->body,
                    'monitor' => $message->monitor->name,
                    'type' => $message->monitor->type->value,
                    'error' => $message->error,
                ],
            ];
        }

        Http::timeout(10)->post(self::ENDPOINT, $payload)->throw();
    }
}
