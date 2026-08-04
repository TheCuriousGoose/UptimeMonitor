<?php

namespace App\Monitoring\Notifiers;

use App\Models\NotificationChannel;
use App\Monitoring\AlertEvent;
use App\Monitoring\AlertMessage;
use Illuminate\Support\Facades\Http;

/**
 * Opsgenie Alert API v2.
 *
 * Alerts are keyed by alias (the monitor uuid) so a recovery closes the alert
 * the outage opened rather than piling up duplicates.
 */
class OpsgenieNotifier implements Notifier
{
    private const BASE = 'https://api.opsgenie.com/v2/alerts';

    public function send(NotificationChannel $channel, AlertMessage $message): void
    {
        $apiKey = $channel->destination();

        if ($apiKey === '') {
            return;
        }

        $alias = 'monitor-'.$message->monitor->uuid;
        $request = Http::timeout(10)->withHeaders([
            'Authorization' => 'GenieKey '.$apiKey,
        ]);

        if ($message->event === AlertEvent::Down) {
            $request->post(self::BASE, [
                'message' => $message->title(),
                'alias' => $alias,
                'description' => $message->body(),
                'priority' => 'P2',
                'source' => config('app.name'),
                'details' => [
                    'monitor' => $message->monitor->name,
                    'url' => $message->monitor->url,
                    'type' => $message->monitor->type->value,
                    'error' => (string) $message->error,
                ],
            ])->throw();

            return;
        }

        $request->post(self::BASE.'/'.urlencode($alias).'/close?identifierType=alias', [
            'source' => config('app.name'),
            'note' => $message->body(),
        ])->throw();
    }
}
