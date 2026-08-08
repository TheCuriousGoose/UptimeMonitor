<?php

namespace App\Monitoring\Notifiers;

use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;

/**
 * Microsoft Teams incoming webhook, using the MessageCard format that Teams
 * connectors render. Teams has no incident lifecycle, so a recovery is just
 * a second card rather than a resolve.
 */
class TeamsNotifier extends HttpNotifier
{
    protected function payload(AlertMessage $message, RenderedAlert $text): array
    {
        return [
            '@type' => 'MessageCard',
            '@context' => 'https://schema.org/extensions',
            'themeColor' => strtoupper(ltrim($message->event->color(), '#')),
            'summary' => $text->title,
            'title' => $text->title,
            'text' => $text->body,
            'sections' => [[
                'facts' => [
                    ['name' => 'Monitor', 'value' => $message->monitor->name],
                    ['name' => 'Target', 'value' => $message->monitor->url],
                    ['name' => 'Status', 'value' => $message->event->label()],
                    ['name' => 'When', 'value' => $message->occurredAt->toDayDateTimeString()],
                ],
            ]],
        ];
    }
}
