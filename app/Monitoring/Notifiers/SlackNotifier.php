<?php

namespace App\Monitoring\Notifiers;

use App\Models\NotificationChannel;
use App\Monitoring\AlertEvent;
use App\Monitoring\AlertMessage;
use Illuminate\Support\Facades\Http;

class SlackNotifier implements Notifier
{
    public function send(NotificationChannel $channel, AlertMessage $message): void
    {
        $url = $channel->destination();

        if ($url === '') {
            return;
        }

        $isDown = $message->event === AlertEvent::Down;

        Http::timeout(10)->post($url, [
            'text' => ($isDown ? ':red_circle: ' : ':large_green_circle: ').$message->title(),
            'attachments' => [[
                'color' => $isDown ? '#dc2626' : '#16a34a',
                'text' => $message->body(),
                'ts' => $message->occurredAt->getTimestamp(),
            ]],
        ])->throw();
    }
}
