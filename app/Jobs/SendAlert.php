<?php

namespace App\Jobs;

use App\Models\NotificationChannel;
use App\Monitoring\AlertMessage;
use App\Monitoring\Notifiers\NotifierRegistry;
use App\Monitoring\QueueResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Alerts are delivered out of band so a slow or failing webhook never
 * delays the check pipeline.
 */
class SendAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(
        public readonly NotificationChannel $channel,
        public readonly AlertMessage $message,
    ) {
        $this->onQueue(app(QueueResolver::class)->for(QueueResolver::LANE_ALERTS));
    }

    public function handle(NotifierRegistry $registry): void
    {
        $registry->resolve($this->channel->type->value)->send($this->channel, $this->message);
    }
}
