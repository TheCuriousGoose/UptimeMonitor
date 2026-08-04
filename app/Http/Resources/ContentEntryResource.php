<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Summary shape only — no body. Listing pages never need it, and the two
 * places that do (the admin editor and the public reader) attach exactly the
 * form they want: raw markdown for editing, rendered HTML for reading.
 */
class ContentEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type->value,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'version' => $this->version,
            'category' => $this->category,
            'sort_order' => $this->sort_order,
            'published_at' => $this->published_at?->toIso8601String(),
            'is_published' => $this->isPublished(),
            'author' => $this->whenLoaded(
                'author',
                fn () => (new UserResource($this->author))->resolve(),
            ),
        ];
    }
}
