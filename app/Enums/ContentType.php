<?php

namespace App\Enums;

enum ContentType: string
{
    case Doc = 'doc';
    case Post = 'post';
    case Changelog = 'changelog';

    /** The public URL segment this type lives under. */
    public function segment(): string
    {
        return match ($this) {
            self::Doc => 'docs',
            self::Post => 'blog',
            self::Changelog => 'changelog',
        };
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
     * Docs read as a hand-ordered manual; the other two are reverse
     * chronological feeds.
     */
    public function isManuallyOrdered(): bool
    {
        return $this === self::Doc;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
