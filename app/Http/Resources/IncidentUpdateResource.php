<?php

namespace App\Http\Resources;

use App\Content\MarkdownRenderer;
use App\Http\Resources\Concerns\ResolvesRelations;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentUpdateResource extends JsonResource
{
    use ResolvesRelations;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'body' => $this->body,
            'body_html' => app(MarkdownRenderer::class)->toHtml($this->body),
            'status' => $this->status?->value,
            'is_public' => $this->is_public,
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => $this->whenLoadedResource('author', UserResource::class),
        ];
    }
}
