<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
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
            // Nested resources are resolved here: left as resource objects they
            // would each pick up their own "data" envelope when serialised.
            'monitor' => $this->whenLoaded(
                'monitor',
                fn () => (new MonitorResource($this->monitor))->resolve(),
            ),
        ];
    }
}
