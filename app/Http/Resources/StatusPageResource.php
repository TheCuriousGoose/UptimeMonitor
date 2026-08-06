<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'is_published' => $this->is_published,
            'show_incidents' => $this->show_incidents,
            // Always the resolved theme, never the raw column: the editor
            // binds straight to these keys and should never see a null.
            'theme' => $this->resolvedTheme()->toArray(),
            'public_url' => route('status.show', $this->slug),
            'monitors_count' => $this->whenCounted('monitors'),
            'monitors' => $this->whenLoaded(
                'monitors',
                fn () => MonitorResource::collection($this->monitors)->resolve(),
            ),
        ];
    }
}
