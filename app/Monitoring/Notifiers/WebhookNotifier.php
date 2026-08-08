<?php

namespace App\Monitoring\Notifiers;

use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;

/**
 * Posts a plain JSON payload, so anything that can receive a webhook
 * (Zapier, n8n, a custom endpoint) can consume alerts.
 */
class WebhookNotifier extends HttpNotifier
{
    protected function payload(AlertMessage $message, RenderedAlert $text): array
    {
        return [
            'event' => $message->event->value,
            // The structured fields below stay verbatim so consumers can parse
            // them; only the human-readable pair honours a custom template.
            'title' => $text->title,
            'message' => $text->body,
            'occurred_at' => $message->occurredAt->toIso8601String(),
            'monitor' => [
                'uuid' => $message->monitor->uuid,
                'name' => $message->monitor->name,
                'url' => $message->monitor->url,
                'type' => $message->monitor->type->value,
            ],
            'error' => $message->error,
        ];
    }
}
