<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesRelations;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    use ResolvesRelations;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'started_at' => $this->started_at->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'cause' => $this->cause,
            'failed_checks' => $this->failed_checks,
            'duration_seconds' => $this->durationSeconds(),
            'is_ongoing' => $this->isOngoing(),
            'is_maintenance' => (bool) $this->is_maintenance,
            'acknowledged_at' => $this->acknowledged_at?->toIso8601String(),
            'is_acknowledged' => $this->isAcknowledged(),
            'acknowledged_by' => $this->whenLoadedResource('acknowledgedBy', UserResource::class),
            'updates' => $this->whenLoadedCollection('updates', IncidentUpdateResource::class),
            'monitor' => $this->whenLoadedResource('monitor', MonitorResource::class),
        ];
    }
}
