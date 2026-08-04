<?php

namespace App\Http\Controllers;

use App\Content\MarkdownRenderer;
use App\Enums\ContentType;
use App\Http\Resources\ContentEntryResource;
use App\Models\ContentEntry;
use Inertia\Inertia;

/**
 * Public readers for docs, blog and changelog. Everything here is
 * unauthenticated and only ever exposes published entries — a draft or a
 * future-dated post must be unreachable by guessing its slug.
 */
class ContentController extends Controller
{
    public function __construct(private readonly MarkdownRenderer $markdown) {}

    public function docs()
    {
        return Inertia::render('content/Docs', [
            'entries' => $this->publishedOfType(ContentType::Doc),
        ]);
    }

    public function blog()
    {
        return Inertia::render('content/Blog', [
            'entries' => $this->publishedOfType(ContentType::Post),
        ]);
    }

    public function changelog()
    {
        return Inertia::render('content/Changelog', [
            'entries' => $this->publishedOfType(ContentType::Changelog),
        ]);
    }

    public function show(string $segment, string $slug)
    {
        $type = collect(ContentType::cases())
            ->first(fn (ContentType $case) => $case->segment() === $segment);

        abort_if($type === null, 404);

        $entry = ContentEntry::query()
            ->ofType($type)
            ->published()
            ->where('slug', $slug)
            ->with('author')
            ->firstOrFail();

        return Inertia::render('content/Show', [
            'entry' => (new ContentEntryResource($entry))->resolve(),
            'bodyHtml' => $this->markdown->toHtml($entry->body),
            // Siblings power the docs sidebar and the "more posts" list.
            'siblings' => $this->publishedOfType($type),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function publishedOfType(ContentType $type): array
    {
        return ContentEntryResource::collection(
            ContentEntry::query()
                ->ofType($type)
                ->published()
                ->inReadingOrder($type)
                ->with('author')
                ->get(),
        )->resolve();
    }
}
