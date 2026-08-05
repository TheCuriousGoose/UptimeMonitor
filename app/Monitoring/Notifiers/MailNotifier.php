<?php

namespace App\Monitoring\Notifiers;

use App\Models\NotificationChannel;
use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;
use App\Notifications\MonitorAlertNotification;
use Illuminate\Support\Facades\Notification;

class MailNotifier implements Notifier
{
    public function send(NotificationChannel $channel, AlertMessage $message, RenderedAlert $text): void
    {
        $address = $channel->destination();

        if ($address === '') {
            return;
        }

        Notification::route('mail', $address)
            ->notify(new MonitorAlertNotification($message, $text));
    }
}
