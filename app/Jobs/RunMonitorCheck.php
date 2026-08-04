<?php

namespace App\Jobs;

use App\Checkers\CheckerRegistry;
use App\Models\Monitor;
use App\Monitoring\QueueResolver;
use App\Monitoring\StatusEvaluator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunMonitorCheck implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly Monitor $monitor)
    {
        $lane = app(CheckerRegistry::class)->resolve($this->monitor->type->value)->queue();

        $this->onQueue(app(QueueResolver::class)->for($lane));
    }

    public function handle(CheckerRegistry $registry, StatusEvaluator $evaluator): void
    {
        $result = $registry->resolve($this->monitor->type->value)->check($this->monitor);

        $evaluator->record($this->monitor, $result);
    }
}
