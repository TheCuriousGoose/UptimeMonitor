<?php

namespace App\Monitoring\Notifiers;

use App\Models\NotificationChannel;
use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;

interface Notifier
{
    /**
     * $text carries the wording to send — the channel's template if it has one,
     * the built-in phrasing otherwise. Use it in preference to re-deriving text
     * from $message, which is here for the structured fields (event, monitor,
     * error, timestamps) that payloads need.
     */
    public function send(NotificationChannel $channel, AlertMessage $message, RenderedAlert $text): void;
}
