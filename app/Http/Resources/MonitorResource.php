<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'url' => $this->url,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'timeout' => $this->timeout,
            'check_interval' => $this->check_interval,
            'created_by' => $this->whenLoaded('createdBy'),
            'monitor_checks' => $this->whenLoaded('monitorChecks'),
            'is_up' => $this->latest_is_up,
        ];
    }
}
