<?php

namespace App\Monitoring\Notifiers;

use App\Models\NotificationChannel;
use App\Monitoring\AlertEvent;
use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;
use Illuminate\Support\Facades\Http;

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
class GoogleChatNotifier implements Notifier
{
    public function send(NotificationChannel $channel, AlertMessage $message, RenderedAlert $text): void
    {
        $url = $channel->destination();

        if ($url === '') {
            return;
        }

        $isDown = $message->event === AlertEvent::Down;

        Http::timeout(10)->post($url, [
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
                        'widgets' => array_values(array_filter([
                            ['textParagraph' => ['text' => $text->body]],
                            ['decoratedText' => [
                                'topLabel' => 'Status',
                                'text' => $isDown ? 'Down' : 'Recovered',
                            ]],
                            ['decoratedText' => [
                                'topLabel' => 'Target',
                                'text' => $message->monitor->url,
                            ]],
                            ['decoratedText' => [
                                'topLabel' => 'When',
                                'text' => $message->occurredAt->toDayDateTimeString(),
                            ]],
                        ])),
                    ]],
                ],
            ]],
        ])->throw();
    }
}
