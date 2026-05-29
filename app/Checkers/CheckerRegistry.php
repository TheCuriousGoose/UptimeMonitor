<?php

namespace App\Checkers;

use InvalidArgumentException;

class CheckerRegistry
{
    /** @param array<string, class-string<Checker>> $map */
    public function __construct(private readonly array $map) {}

    public function resolve(string $type): Checker
    {
        if (! isset($this->map[$type])) {
            throw new InvalidArgumentException("Unknown monitor type: [{$type}]");
        }

        return app($this->map[$type]);
    }
}
