<?php

namespace App\Monitoring;

/**
 * The final text a notifier should send, after the channel's own templates
 * have been applied. Notifiers receive this rather than re-deriving the
 * wording, so a custom template reaches every surface that can carry text.
 */
final readonly class RenderedAlert
{
    public function __construct(
        public string $title,
        public string $body,
    ) {}
}
