<?php

namespace App\Monitoring\Notifiers;

use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;

class DiscordNotifier extends HttpNotifier
{
    protected function payload(AlertMessage $message, RenderedAlert $text): array
    {
        return [
            'embeds' => [[
                'title' => $text->title,
                'description' => $text->body,
                // Discord wants a decimal colour value.
                'color' => hexdec(ltrim($message->event->color(), '#')),
                'timestamp' => $message->occurredAt->toIso8601String(),
            ]],
        ];
    }
}
