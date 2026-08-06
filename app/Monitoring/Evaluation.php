<?php

namespace App\Monitoring;

use App\Models\Incident;
use App\Models\MonitorCheck;

/**
 * Everything one recorded check decided.
 *
 * Replaces the tuple StatusEvaluator::persist() used to return. The tuple was
 * already three slots wide and about to become five; naming the parts stops
 * every caller from having to remember the order.
 */
final readonly class Evaluation
{
    public function __construct(
        public MonitorCheck $check,
        public Transition $transition,
        public Degradation $degradation,
        public ?Incident $incident = null,
        public bool $underMaintenance = false,
    ) {}
}
