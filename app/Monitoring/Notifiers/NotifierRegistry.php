<?php

namespace App\Monitoring\Notifiers;

use InvalidArgumentException;

class NotifierRegistry
{
    /** @param array<string, class-string<Notifier>> $map */
    public function __construct(private readonly array $map) {}

    public function resolve(string $type): Notifier
    {
        if (! isset($this->map[$type])) {
            throw new InvalidArgumentException("Unknown notification channel type: [{$type}]");
        }

        return app($this->map[$type]);
    }
}
