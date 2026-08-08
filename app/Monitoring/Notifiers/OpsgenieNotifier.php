<?php

namespace App\Monitoring\Notifiers;

use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;
use Illuminate\Support\Facades\Http;

/**
 * Opsgenie Alert API v2.
 *
 * Alerts are keyed by alias so a recovery closes the alert the outage opened
 * rather than piling up duplicates. Creating against a live alias is how
 * Opsgenie deduplicates, so a reminder re-posts rather than opening a second
 * alert.
 */
class OpsgenieNotifier extends BaseNotifier
{
    private const BASE = 'https://api.opsgenie.com/v2/alerts';

    private const PRIORITIES = [
        'error' => 'P2',
        'warning' => 'P3',
        'info' => 'P3',
    ];

    protected function deliver(string $destination, AlertMessage $message, RenderedAlert $text): void
    {
        $alias = $message->dedupeKey();
        $request = Http::timeout(static::TIMEOUT)->withHeaders([
            'Authorization' => 'GenieKey '.$destination,
        ]);

        // Branching on isResolution() rather than comparing against Down:
        // an identity check sent a reminder about a still-open outage — and
        // every degradation — down the close path, resolving a live alert.
        if ($message->event->isResolution()) {
            $request->post(self::BASE.'/'.urlencode($alias).'/close?identifierType=alias', [
                'source' => config('app.name'),
                'note' => $text->body,
            ])->throw();

            return;
        }

        $request->post(self::BASE, [
            'message' => $text->title,
            'alias' => $alias,
            'description' => $text->body,
            'priority' => self::PRIORITIES[$message->event->severity()],
            'source' => config('app.name'),
            'details' => [
                'monitor' => $message->monitor->name,
                'url' => $message->monitor->url,
                'type' => $message->monitor->type->value,
                'status' => $message->event->label(),
                'error' => (string) $message->error,
            ],
        ])->throw();
    }
}
