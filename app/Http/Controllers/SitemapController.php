<?php

namespace App\Http\Controllers;

use App\Enums\ContentType;
use App\Models\ContentEntry;
use App\Models\StatusPage;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    /**
     * Static marketing routes. Legal pages are included because they are
     * content entries whose text differs per deployment.
     */
    private const STATIC_ROUTES = [
        'home', 'features', 'about', 'contact', 'roadmap',
        'privacy', 'terms', 'docs.index', 'blog.index', 'changelog.index',
    ];

    public function index(): Response
    {
        $urls = collect(self::STATIC_ROUTES)
            ->map(fn (string $name) => ['loc' => route($name), 'lastmod' => null]);

        $urls = $urls->concat($this->publishedContent())->concat($this->publishedStatusPages());

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string}>
     */
    private function publishedContent()
    {
        return ContentEntry::query()
            ->published()
            ->whereIn('type', [ContentType::Doc, ContentType::Post, ContentType::Changelog])
            ->get(['type', 'slug', 'updated_at'])
            ->map(fn (ContentEntry $entry) => [
                'loc' => route('content.show', [
                    'segment' => $entry->type->segment(),
                    'slug' => $entry->slug,
                ]),
                'lastmod' => $entry->updated_at?->toDateString(),
            ]);
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string}>
     */
    private function publishedStatusPages()
    {
        // Unpublished pages 404, so listing them would advertise a dead link.
        return StatusPage::query()
            ->where('is_published', true)
            ->get(['slug', 'updated_at'])
            ->map(fn (StatusPage $page) => [
                'loc' => route('status.show', $page->slug),
                'lastmod' => $page->updated_at?->toDateString(),
            ]);
    }
}
