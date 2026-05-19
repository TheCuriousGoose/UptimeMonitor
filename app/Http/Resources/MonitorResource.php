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
            'timeout' => $this->timeout,
            'check_interval' => $this->check_interval,
            'created_by' => $this->createdBy,
            'monitor_checks' => $this->monitorChecks,
            'is_up' => $this->isUp(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
