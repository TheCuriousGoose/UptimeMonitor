<?php

namespace App\StatusPages;

use App\Enums\StatusPageMode;

/**
 * Turns whatever a page owner typed into a value that is safe to interpolate
 * into a public, unauthenticated page.
 *
 * Gathered into one class because they are a single concern wearing several
 * names: nothing here formats or derives anything, every method answers "what
 * is the safe form of this input, and what do we use when there isn't one".
 * Keeping them beside the palette maths meant the security-critical half of
 * StatusPageTheme was interleaved with the cosmetic half.
 */
final class ThemeValues
{
    /**
     * Webfont containers we will build an `@font-face` rule for, mapped to the
     * `format()` hint. Deliberately files, not stylesheets: pointing at a
     * remote CSS file would hand a third party full control of the page, which
     * is a much larger door than "use our corporate typeface".
     */
    public const FONT_FORMATS = [
        'woff2' => 'woff2',
        'woff' => 'woff',
        'ttf' => 'truetype',
        'otf' => 'opentype',
    ];

    /**
     * How many header/footer links a page may carry. Enough for "back to the
     * website" and a support route, not enough to become a navigation bar.
     */
    public const MAX_LINKS = 5;

    public static function mode(mixed $value): StatusPageMode
    {
        return (is_string($value) ? StatusPageMode::tryFrom($value) : null) ?? StatusPageMode::System;
    }

    public static function color(mixed $value): ?string
    {
        return Color::parse(is_string($value) ? $value : null)?->toHex();
    }

    /**
     * A CSS font stack, reduced to the characters family names are made of.
     *
     * Semicolons, braces, backslashes and parentheses are what would let a
     * stack close the declaration and start writing rules of its own, so they
     * are removed rather than escaped — a family name never needs them, and
     * `url(...)` in particular must not survive.
     */
    public static function fontFamily(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $stack = trim(preg_replace('/\s+/', ' ', (string) preg_replace('/[^-a-zA-Z0-9 ,\'"_]/', '', $value)));
        $stack = trim($stack, ' ,');

        return $stack === '' ? null : mb_substr($stack, 0, 160);
    }

    /**
     * The first family in a stack, unquoted — the name an `@font-face` binds to.
     */
    public static function primaryFamily(string $stack): ?string
    {
        $first = trim((string) strtok($stack, ','), " \t\n\r\0\x0B\"'");

        return $first === '' ? null : $first;
    }

    /**
     * A font file we can build an `@font-face` around. Quotes, parentheses,
     * backslashes, angle brackets and whitespace are rejected outright rather
     * than escaped: the URL is emitted inside `url("…")`, and dropping `<`
     * and `>` is what guarantees the stylesheet can never produce a
     * `</style>` for the page to render unescaped.
     */
    public static function fontUrl(mixed $value): ?string
    {
        $url = self::url($value);

        if ($url === null || preg_match('/["\'()<>\\\\\s]/', $url) === 1) {
            return null;
        }

        return self::fontFormat($url) === null ? null : $url;
    }

    /**
     * The `format()` hint for a font URL, or null if it is not one we build a
     * rule for.
     */
    public static function fontFormat(string $url): ?string
    {
        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return self::FONT_FORMATS[$extension] ?? null;
    }

    public static function length(mixed $value, int $min, int $max, int $fallback): int
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        return max($min, min($max, (int) round((float) $value)));
    }

    /**
     * Only absolute http(s) URLs are kept. A relative or `javascript:` value
     * would end up in an `src`/`href` on a public page, so it is dropped here
     * as well as being rejected by the form request.
     */
    public static function url(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true) || parse_url($value, PHP_URL_HOST) === null) {
            return null;
        }

        return mb_substr($value, 0, 255);
    }

    public static function text(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, 255);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    public static function links(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $links = [];

        foreach ($value as $link) {
            if (! is_array($link)) {
                continue;
            }

            $label = self::text($link['label'] ?? null);
            $url = self::url($link['url'] ?? null);

            if ($label === null || $url === null) {
                continue;
            }

            $links[] = ['label' => mb_substr($label, 0, 40), 'url' => $url];

            if (count($links) === self::MAX_LINKS) {
                break;
            }
        }

        return $links;
    }
}
