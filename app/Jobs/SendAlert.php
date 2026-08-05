<?php

namespace App\Jobs;

use App\Models\NotificationChannel;
use App\Monitoring\AlertMessage;
use App\Monitoring\AlertTemplate;
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

    public function handle(NotifierRegistry $registry, AlertTemplate $template): void
    {
        // Rendered once here rather than in each notifier, so a custom template
        // reaches every surface that can carry text — including a test send.
        $text = $template->render($this->message, $this->channel->templates);

        $registry->resolve($this->channel->type->value)
            ->send($this->channel, $this->message, $text);
    }
}
