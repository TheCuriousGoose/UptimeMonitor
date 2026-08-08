<?php

namespace App\Checkers;

use App\Checkers\Support\Stopwatch;
use App\Models\Monitor;
use Throwable;

/**
 * The invariants every check shares: it is timed, and it always produces a
 * CheckResult.
 *
 * The second one is why this is a `final` template rather than a helper.
 * RunMonitorCheck sets tries = 1, so an exception escaping a checker does not
 * retry — it loses the check outright, leaving no row, no status change and a
 * next_check_at that never advances. Only HttpChecker used to guard against
 * that; the probes that shell out or open sockets did not.
 */
abstract class BaseChecker implements Checker
{
    final public function check(Monitor $monitor): CheckResult
    {
        $timer = Stopwatch::start();

        try {
            return $this->probe($monitor, $timer);
        } catch (Throwable $e) {
            return CheckResult::down(
                $this->explain($e->getMessage()),
                $timer->elapsedMs(),
                ['checker' => $monitor->type?->value ?? 'unknown'],
            );
        }
    }

    abstract protected function probe(Monitor $monitor, Stopwatch $timer): CheckResult;

    /**
     * Turn a raw exception message into something a monitor's owner can act
     * on. Most checkers have nothing to add.
     */
    protected function explain(string $message): string
    {
        return $message;
    }
}
