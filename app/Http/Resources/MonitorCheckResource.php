<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitorCheckResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_up' => $this->is_up,
            'response_ms' => $this->response_ms,
            'error' => $this->error,
            'meta' => $this->meta,
            'checked_at' => $this->checked_at->toIso8601String(),
        ];
    }
}
