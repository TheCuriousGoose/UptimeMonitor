<?php

namespace App\Jobs;

use App\Checkers\CheckerRegistry;
use App\Models\Monitor;
use App\Monitoring\EgressBreaker;
use App\Monitoring\QueueResolver;
use App\Monitoring\StatusEvaluator;
use App\Monitoring\TargetIdentity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunMonitorCheck implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly Monitor $monitor)
    {
        $lane = app(CheckerRegistry::class)->resolve($this->monitor->type->value)->queue();

        $this->onQueue(app(QueueResolver::class)->for($lane));
    }

    public function handle(
        CheckerRegistry $registry,
        StatusEvaluator $evaluator,
        ?EgressBreaker $breaker = null,
    ): void {
        $breaker ??= app(EgressBreaker::class);

        $identity = TargetIdentity::forMonitor($this->monitor);

        if ($identity !== null && ! $breaker->attempt($identity)) {
            $this->reportThrottled($identity, $breaker);

            return;
        }

        $result = $registry->resolve($this->monitor->type->value)->check($this->monitor);

        $evaluator->record($this->monitor, $result);
    }

    /**
     * Deliberately records nothing. A skipped check is not a failed one, and
     * writing it as down would invent an incident, page someone, and corrupt
     * the uptime figures for an outage that never happened.
     */
    private function reportThrottled(TargetIdentity $identity, EgressBreaker $breaker): void
    {
        Log::warning('Check skipped: egress budget spent for target domain.', [
            'monitor_uuid' => $this->monitor->uuid,
            'domain' => $identity->domain,
            'limit_per_minute' => $breaker->limit(),
            'retry_in_seconds' => $breaker->availableIn($identity),
        ]);
    }
}
