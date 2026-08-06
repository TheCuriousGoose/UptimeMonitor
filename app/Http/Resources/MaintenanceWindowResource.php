<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceWindowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'recurrence' => $this->recurrence->value,
            'timezone' => $this->timezone,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'cron' => $this->cron,
            'duration_minutes' => $this->duration_minutes,
            'is_active' => $this->is_active,
            'is_active_now' => $this->coversAt(now()),
            // Computed here so the UI never re-implements cron in TS.
            'next_occurrence_at' => $this->nextOccurrenceAt()?->toIso8601String(),
            'monitors' => $this->whenLoaded(
                'monitors',
                fn () => $this->monitors->pluck('uuid')->all(),
            ),
            'monitors_count' => $this->whenCounted('monitors'),
        ];
    }
}
