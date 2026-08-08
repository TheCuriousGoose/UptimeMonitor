<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesRelations;
use App\Monitoring\ConfigMasker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitorResource extends JsonResource
{
    use ResolvesRelations;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'url' => $this->url,
            'type' => $this->type->value,
            'status' => $this->status()->value,
            'is_active' => $this->is_active,
            'timeout' => $this->timeout,
            'interval_seconds' => $this->interval_seconds,
            'confirmation_threshold' => $this->confirmation_threshold,
            'recovery_threshold' => $this->recovery_threshold,
            'degraded_response_ms' => $this->degraded_response_ms,
            'is_degraded' => (bool) $this->is_degraded,
            // Masked: the form needs the shape, never the credential.
            'config' => ConfigMasker::mask($this->resolvedConfig()),
            'paused_reason' => $this->paused_reason,
            'last_checked_at' => $this->last_checked_at?->toIso8601String(),
            'status_changed_at' => $this->status_changed_at?->toIso8601String(),
            'created_by' => $this->whenLoadedResource('createdBy', UserResource::class),
            'checks' => $this->whenLoadedCollection('checks', MonitorCheckResource::class),
            'notification_channels' => $this->whenLoadedCollection('notificationChannels', NotificationChannelResource::class),
        ];
    }
}
