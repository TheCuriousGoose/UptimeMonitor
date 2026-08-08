<?php

namespace App\Monitoring\Notifiers;

use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;
use Illuminate\Support\Facades\Http;

/**
 * PagerDuty Events API v2.
 *
 * Events carry a dedup_key so a recovery resolves the very incident the
 * outage opened, instead of leaving a stale page open forever.
 */
class PagerDutyNotifier extends BaseNotifier
{
    private const ENDPOINT = 'https://events.pagerduty.com/v2/enqueue';

    protected function deliver(string $destination, AlertMessage $message, RenderedAlert $text): void
    {
        // Branching on isResolution() rather than comparing against Down:
        // with an identity check, a "degraded" alert resolved the live page
        // for an outage that was still ongoing.
        $resolving = $message->event->isResolution();

        $payload = [
            'routing_key' => $destination,
            'event_action' => $resolving ? 'resolve' : 'trigger',
            'dedup_key' => $message->dedupeKey(),
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
                    'status' => $message->event->label(),
                    'error' => $message->error,
                ],
            ];
        }

        Http::timeout(static::TIMEOUT)->post(self::ENDPOINT, $payload)->throw();
    }
}
