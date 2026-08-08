<?php

namespace App\Monitoring\Notifiers;

use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;
use App\Notifications\MonitorAlertNotification;
use Illuminate\Support\Facades\Notification;

class MailNotifier extends BaseNotifier
{
    protected function deliver(string $destination, AlertMessage $message, RenderedAlert $text): void
    {
        Notification::route('mail', $destination)
            ->notify(new MonitorAlertNotification($message, $text));
    }
}
