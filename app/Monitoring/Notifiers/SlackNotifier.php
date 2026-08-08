<?php

namespace App\Monitoring\Notifiers;

use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;

class SlackNotifier extends HttpNotifier
{
    private const EMOJI = [
        'error' => ':red_circle:',
        'warning' => ':large_yellow_circle:',
        'info' => ':large_green_circle:',
    ];

    protected function payload(AlertMessage $message, RenderedAlert $text): array
    {
        return [
            'text' => self::EMOJI[$message->event->severity()].' '.$text->title,
            'attachments' => [[
                'color' => $message->event->color(),
                'text' => $text->body,
                'ts' => $message->occurredAt->getTimestamp(),
            ]],
        ];
    }
}
