<?php

namespace App\Enums;

enum ContentType: string
{
    case Doc = 'doc';
    case Post = 'post';
    case Changelog = 'changelog';
    case Legal = 'legal';

    /** The public URL segment this type lives under. */
    public function segment(): string
    {
        return match ($this) {
            self::Doc => 'docs',
            self::Post => 'blog',
            self::Changelog => 'changelog',
            self::Legal => 'legal',
        };
    }

    /**
     * Legal pages are reached at their own top-level URLs (/privacy, /terms)
     * rather than through an index, so they are excluded from listings.
     */
    public function hasPublicIndex(): bool
    {
        return $this !== self::Legal;
    }

    /** Whether entries of this type carry a release version. */
    public function hasVersion(): bool
    {
        return $this === self::Changelog;
    }

    /** Whether entries of this type are grouped into categories. */
    public function hasCategory(): bool
    {
        return $this === self::Doc;
    }

    /**
     * Docs read as a hand-ordered manual; the feeds are reverse chronological.
     */
    public function isManuallyOrdered(): bool
    {
        return $this === self::Doc;
    }

    /**
     * @return array<int, string>
     */
    public static function indexableValues(): array
    {
        return array_values(array_map(
            fn (self $type) => $type->value,
            array_filter(self::cases(), fn (self $type) => $type->hasPublicIndex()),
        ));
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
