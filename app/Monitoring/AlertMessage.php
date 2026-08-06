<?php

namespace App\Monitoring;

use App\Models\Incident;
use App\Models\Monitor;
use Carbon\CarbonInterface;

final readonly class AlertMessage
{
    private function __construct(
        public Monitor $monitor,
        public AlertEvent $event,
        public CarbonInterface $occurredAt,
        public ?string $error = null,
        public ?Incident $incident = null,
        public ?int $responseMs = null,
        public ?int $thresholdMs = null,
    ) {}

    public static function down(Monitor $monitor, ?string $error, ?Incident $incident = null): self
    {
        return new self($monitor, AlertEvent::Down, now(), $error, $incident);
    }

    public static function recovered(Monitor $monitor, ?Incident $incident = null): self
    {
        return new self($monitor, AlertEvent::Recovered, now(), null, $incident);
    }

    public static function degraded(Monitor $monitor, int $responseMs, int $thresholdMs): self
    {
        return new self(
            $monitor,
            AlertEvent::Degraded,
            now(),
            responseMs: $responseMs,
            thresholdMs: $thresholdMs,
        );
    }

    public static function improved(Monitor $monitor, int $responseMs): self
    {
        return new self($monitor, AlertEvent::Improved, now(), responseMs: $responseMs);
    }

    public function title(): string
    {
        return match ($this->event) {
            AlertEvent::Down => "{$this->monitor->name} is DOWN",
            AlertEvent::Recovered => "{$this->monitor->name} is back UP",
            AlertEvent::Degraded => "{$this->monitor->name} is SLOW",
            AlertEvent::Improved => "{$this->monitor->name} is back to normal speed",
        };
    }

    public function body(): string
    {
        return match ($this->event) {
            AlertEvent::Down => "{$this->monitor->name} ({$this->monitor->url}) stopped responding: "
                .($this->error ?: 'the check failed.'),

            AlertEvent::Recovered => "{$this->monitor->name} ({$this->monitor->url}) is responding again"
                .($this->incident
                    ? ' after '.AlertTemplate::humanDuration($this->incident->durationSeconds())
                    : '').'.',

            AlertEvent::Degraded => "{$this->monitor->name} ({$this->monitor->url}) is still responding but "
                ."took {$this->responseMs} ms, over the {$this->thresholdMs} ms threshold.",

            AlertEvent::Improved => "{$this->monitor->name} ({$this->monitor->url}) is back under its "
                ."response time threshold at {$this->responseMs} ms.",
        };
    }
}
