<?php

namespace App\Monitoring\Notifiers;

use App\Models\NotificationChannel;
use App\Monitoring\AlertMessage;

interface Notifier
{
    public function send(NotificationChannel $channel, AlertMessage $message): void;
}
