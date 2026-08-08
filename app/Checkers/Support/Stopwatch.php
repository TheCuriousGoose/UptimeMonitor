<?php

namespace App\Checkers\Support;

/**
 * Monotonic elapsed time for a single check.
 *
 * Every checker reports a duration, and each one used to spell the same
 * `hrtime` division out by hand. Passed to the probe rather than held on the
 * checker, so nothing depends on how the container hands the checker out.
 */
final readonly class Stopwatch
{
    private function __construct(private float $startedAt) {}

    public static function start(): self
    {
        return new self(hrtime(true));
    }

    public function elapsedMs(): int
    {
        return (int) ((hrtime(true) - $this->startedAt) / 1_000_000);
    }
}
