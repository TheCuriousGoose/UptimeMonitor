<?php

namespace App\Monitoring\Notifiers;

use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;
use Illuminate\Support\Facades\Http;

/**
 * A notifier that delivers by POSTing JSON to the channel's destination.
 *
 * Subclasses describe the payload and inherit the transport. The two
 * incident-tracking services extend {@see BaseNotifier} directly instead,
 * because their URL and headers vary with the event.
 */
abstract class HttpNotifier extends BaseNotifier
{
    protected function deliver(string $destination, AlertMessage $message, RenderedAlert $text): void
    {
        Http::timeout(static::TIMEOUT)
            ->post($destination, $this->payload($message, $text))
            ->throw();
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function payload(AlertMessage $message, RenderedAlert $text): array;
}
