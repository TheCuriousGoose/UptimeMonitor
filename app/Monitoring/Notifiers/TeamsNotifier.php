<?php

namespace App\Monitoring\Notifiers;

use App\Models\NotificationChannel;
use App\Monitoring\AlertEvent;
use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;
use Illuminate\Support\Facades\Http;

/**
 * Microsoft Teams incoming webhook, using the MessageCard format that Teams
 * connectors render. Teams has no incident lifecycle, so a recovery is just
 * a second card rather than a resolve.
 */
class TeamsNotifier implements Notifier
{
    public function send(NotificationChannel $channel, AlertMessage $message, RenderedAlert $text): void
    {
        $url = $channel->destination();

        if ($url === '') {
            return;
        }

        $isDown = $message->event === AlertEvent::Down;

        Http::timeout(10)->post($url, [
            '@type' => 'MessageCard',
            '@context' => 'https://schema.org/extensions',
            'themeColor' => $isDown ? 'DC2626' : '16A34A',
            'summary' => $text->title,
            'title' => $text->title,
            'text' => $text->body,
            'sections' => [[
                'facts' => [
                    ['name' => 'Monitor', 'value' => $message->monitor->name],
                    ['name' => 'Target', 'value' => $message->monitor->url],
                    ['name' => 'When', 'value' => $message->occurredAt->toDayDateTimeString()],
                ],
            ]],
        ])->throw();
    }
}
