<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationChannelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'type' => $this->type->value,
            // Masked, never raw: for PagerDuty and Opsgenie the destination is
            // an API credential, and this resource is serialised into the page.
            'destination' => $this->maskedDestination(),
            'is_active' => $this->is_active,
            'monitors_count' => $this->whenCounted('monitors'),
        ];
    }
}
