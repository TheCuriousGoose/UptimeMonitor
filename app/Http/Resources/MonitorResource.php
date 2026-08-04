<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitorResource extends JsonResource
{
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
            'config' => $this->resolvedConfig(),
            'last_checked_at' => $this->last_checked_at?->toIso8601String(),
            'status_changed_at' => $this->status_changed_at?->toIso8601String(),
            // Nested resources are resolved here: left as resource objects they
            // would each pick up their own "data" envelope when serialised.
            'created_by' => $this->whenLoaded(
                'createdBy',
                fn () => (new UserResource($this->createdBy))->resolve(),
            ),
            'checks' => $this->whenLoaded(
                'checks',
                fn () => MonitorCheckResource::collection($this->checks)->resolve(),
            ),
            'notification_channels' => $this->whenLoaded(
                'notificationChannels',
                fn () => NotificationChannelResource::collection($this->notificationChannels)->resolve(),
            ),
        ];
    }
}
