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
    ) {}

    public static function down(Monitor $monitor, ?string $error, ?Incident $incident = null): self
    {
        return new self($monitor, AlertEvent::Down, now(), $error, $incident);
    }

    public static function recovered(Monitor $monitor, ?Incident $incident = null): self
    {
        return new self($monitor, AlertEvent::Recovered, now(), null, $incident);
    }

    public function title(): string
    {
        return $this->event === AlertEvent::Down
            ? "{$this->monitor->name} is DOWN"
            : "{$this->monitor->name} is back UP";
    }

    public function body(): string
    {
        if ($this->event === AlertEvent::Down) {
            return "{$this->monitor->name} ({$this->monitor->url}) stopped responding: "
                .($this->error ?: 'the check failed.');
        }

        $downtime = $this->incident
            ? ' after '.$this->humanDowntime($this->incident->durationSeconds())
            : '';

        return "{$this->monitor->name} ({$this->monitor->url}) is responding again{$downtime}.";
    }

    private function humanDowntime(int $seconds): string
    {
        return match (true) {
            $seconds < 60 => "{$seconds}s",
            $seconds < 3600 => floor($seconds / 60).'m',
            default => floor($seconds / 3600).'h '.floor(($seconds % 3600) / 60).'m',
        };
    }
}
