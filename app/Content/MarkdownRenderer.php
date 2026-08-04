<?php

namespace App\Content;

use Illuminate\Support\Str;

/**
 * Content bodies are authored as markdown and rendered to HTML that the
 * frontend injects with v-html. Raw HTML in the source is stripped rather
 * than escaped, and unsafe link schemes (javascript:, data:) are dropped,
 * so an author cannot land script in a reader's page.
 *
 * Rendering lives here rather than at each call site so those options can
 * never be forgotten by one of them.
 */
class MarkdownRenderer
{
    private const OPTIONS = [
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
        'max_nesting_level' => 50,
    ];

    public function toHtml(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        return Str::markdown($markdown, self::OPTIONS);
    }

    /**
     * Plain-text preview for list pages and meta descriptions.
     */
    public function toExcerpt(?string $markdown, int $length = 200): string
    {
        $text = trim(strip_tags($this->toHtml($markdown)));

        return Str::limit(preg_replace('/\s+/', ' ', $text) ?? '', $length);
    }
}
