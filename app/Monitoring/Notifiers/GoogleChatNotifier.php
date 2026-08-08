<?php

namespace App\Monitoring\Notifiers;

use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;

/**
 * Google Chat incoming webhook, posting a card.
 *
 * `text` is sent alongside the card rather than instead of it: Chat uses it
 * for the notification preview and for clients that cannot render cards, so
 * dropping it would leave those readers with an empty-looking message.
 *
 * Like Teams, Chat has no incident lifecycle — a recovery is a second message
 * rather than a resolve on the first.
 */
class GoogleChatNotifier extends HttpNotifier
{
    protected function payload(AlertMessage $message, RenderedAlert $text): array
    {
        return [
            'text' => $text->title,
            'cardsV2' => [[
                'cardId' => 'monitor-alert',
                'card' => [
                    'header' => [
                        'title' => $text->title,
                        'subtitle' => $message->monitor->name,
                        // Chat renders these as a coloured chip rather than
                        // letting us set a theme colour the way Teams does.
                        'imageType' => 'CIRCLE',
                    ],
                    'sections' => [[
                        'widgets' => [
                            ['textParagraph' => ['text' => $text->body]],
                            ['decoratedText' => [
                                'topLabel' => 'Status',
                                'text' => $message->event->label(),
                            ]],
                            ['decoratedText' => [
                                'topLabel' => 'Target',
                                'text' => $message->monitor->url,
                            ]],
                            ['decoratedText' => [
                                'topLabel' => 'When',
                                'text' => $message->occurredAt->toDayDateTimeString(),
                            ]],
                        ],
                    ]],
                ],
            ]],
        ];
    }
}
