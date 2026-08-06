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
            'alert_scope' => $this->alert_scope->value,
            'renotify_minutes' => $this->renotify_minutes,
            'renotify_limit' => $this->renotify_limit,
            // Trimmed to HH:MM — the seconds a TIME column carries are noise
            // the form would have to strip anyway.
            'quiet_hours_start' => $this->quiet_hours_start
                ? substr((string) $this->quiet_hours_start, 0, 5)
                : null,
            'quiet_hours_end' => $this->quiet_hours_end
                ? substr((string) $this->quiet_hours_end, 0, 5)
                : null,
            'quiet_hours_timezone' => $this->quiet_hours_timezone,
            // Not secret, unlike the destination: templates are wording the
            // user typed and the form needs them back to edit.
            'templates' => $this->templates,
            'monitors_count' => $this->whenCounted('monitors'),
            'monitors' => $this->whenLoaded(
                'monitors',
                fn () => $this->monitors->pluck('uuid')->all(),
            ),
        ];
    }
}
