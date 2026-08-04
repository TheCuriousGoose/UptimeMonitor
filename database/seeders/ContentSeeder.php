<?php

namespace Database\Seeders;

use App\Enums\ContentType;
use App\Models\ContentEntry;
use App\Models\User;
use Database\Seeders\Content\BlogContent;
use Database\Seeders\Content\DocsContent;
use Database\Seeders\Content\LegalContent;
use Illuminate\Database\Seeder;

/**
 * Seeds the starting documentation, blog posts and legal pages.
 *
 * Create-only, keyed on (type, slug). Re-running adds anything missing and
 * leaves everything that already exists untouched — the legal pages exist
 * precisely to be rewritten with real controller details, and a seeder that
 * overwrote them on the next deploy would silently republish placeholders.
 *
 * To restore the shipped text for one entry, delete it and seed again.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->oldest('id')->first();

        foreach (LegalContent::entries() as $entry) {
            $this->upsert(ContentType::Legal, $entry, $author);
        }

        foreach (DocsContent::entries() as $entry) {
            $this->upsert(ContentType::Doc, $entry, $author);
        }

        foreach (BlogContent::entries() as $entry) {
            $this->upsert(ContentType::Post, $entry, $author);
        }
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function upsert(ContentType $type, array $entry, ?User $author): void
    {
        ContentEntry::firstOrCreate(
            ['type' => $type->value, 'slug' => $entry['slug']],
            [
                'title' => $entry['title'],
                'excerpt' => $entry['excerpt'] ?? null,
                'body' => $entry['body'],
                'category' => $entry['category'] ?? null,
                'sort_order' => $entry['sort_order'] ?? 0,
                'published_at' => isset($entry['publishedDaysAgo'])
                    ? now()->subDays($entry['publishedDaysAgo'])
                    : now(),
                'author_id' => $author?->id,
            ],
        );
    }
}
