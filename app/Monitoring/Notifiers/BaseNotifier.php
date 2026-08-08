<?php

namespace App\Monitoring\Notifiers;

use App\Models\NotificationChannel;
use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;

/**
 * Holds the one thing every notifier does before anything else: refuse to
 * deliver a channel that has no destination configured.
 *
 * A channel can lose its destination without being deactivated — a cleared
 * config field, a partially seeded row — and every notifier answered that
 * with its own copy of the same guard.
 */
abstract class BaseNotifier implements Notifier
{
    /**
     * Alerting is already out of band on its own queue, so this bounds a
     * hung endpoint rather than the check pipeline.
     */
    protected const TIMEOUT = 10;

    final public function send(NotificationChannel $channel, AlertMessage $message, RenderedAlert $text): void
    {
        $destination = $channel->destination();

        if ($destination === '') {
            return;
        }

        $this->deliver($destination, $message, $text);
    }

    abstract protected function deliver(string $destination, AlertMessage $message, RenderedAlert $text): void;
}
