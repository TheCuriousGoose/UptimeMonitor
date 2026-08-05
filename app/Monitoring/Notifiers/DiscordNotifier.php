<?php

namespace App\Monitoring\Notifiers;

use App\Models\NotificationChannel;
use App\Monitoring\AlertEvent;
use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;
use Illuminate\Support\Facades\Http;

class DiscordNotifier implements Notifier
{
    public function send(NotificationChannel $channel, AlertMessage $message, RenderedAlert $text): void
    {
        $url = $channel->destination();

        if ($url === '') {
            return;
        }

        $isDown = $message->event === AlertEvent::Down;

        Http::timeout(10)->post($url, [
            'embeds' => [[
                'title' => $text->title,
                'description' => $text->body,
                // Discord wants a decimal colour value.
                'color' => $isDown ? 0xDC2626 : 0x16A34A,
                'timestamp' => $message->occurredAt->toIso8601String(),
            ]],
        ])->throw();
    }
}
