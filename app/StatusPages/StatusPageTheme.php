<?php

namespace App\StatusPages;

use App\Enums\StatusPageMode;

/**
 * A status page's house style.
 *
 * Owners pick a handful of things — a brand colour, maybe a background, a
 * logo, their own typeface — and this class turns that into the full set of
 * custom properties the public page renders with. Surfaces, borders and
 * secondary text are *derived* rather than asked for, so a page stays readable
 * whatever combination someone chooses.
 *
 * Colours, corner rounding, content width and the font stack are all free
 * form: a company's identity rarely lands on one of four presets. What keeps
 * that safe is that no value a page owner typed is interpolated into the
 * stylesheet verbatim — colours are normalised through {@see Color} into
 * `#rrggbb`, lengths are clamped integers, and the font stack is filtered down
 * to a family-name character set. The public page is unauthenticated, so it
 * must not carry a CSS injection surface.
 */
final class StatusPageTheme
{
    public const DEFAULT_BRAND = '#4f46e5';

    public const DEFAULT_UP = '#0ca30c';

    public const DEFAULT_DOWN = '#d03b3b';

    public const DEFAULT_WARNING = '#fab219';

    public const DEFAULT_FONT = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif";

    public const DEFAULT_RADIUS = 4;

    public const DEFAULT_WIDTH = 768;

    public const MIN_RADIUS = 0;

    public const MAX_RADIUS = 32;

    public const MIN_WIDTH = 320;

    public const MAX_WIDTH = 1600;

    /**
     * How many header/footer links a page may carry. Enough for "back to the
     * website" and a support route, not enough to become a navigation bar.
     */
    public const MAX_LINKS = 5;

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

    private const LIGHT_BACKGROUND = '#ffffff';

    private const LIGHT_FOREGROUND = '#111114';

    private const DARK_BACKGROUND = '#0b0b0f';

    private const DARK_FOREGROUND = '#f4f4f6';

    /**
     * @param  array<int, array{label: string, url: string}>  $links
     */
    private function __construct(
        public readonly StatusPageMode $mode,
        public readonly string $fontFamily,
        public readonly ?string $fontUrl,
        public readonly int $radius,
        public readonly int $width,
        public readonly string $brandColor,
        public readonly ?string $background,
        public readonly ?string $foreground,
        public readonly string $upColor,
        public readonly string $downColor,
        public readonly string $warningColor,
        public readonly ?string $logoUrl,
        public readonly ?string $faviconUrl,
        public readonly ?string $footerText,
        public readonly array $links,
    ) {}

    /**
     * Builds a theme from whatever is on the model, filling every gap with a
     * default. Pages created before theming existed store null and land here.
     *
     * @param  array<string, mixed>|null  $attributes
     */
    public static function fromArray(?array $attributes): self
    {
        $attributes ??= [];

        return new self(
            mode: self::mode($attributes['mode'] ?? null),
            fontFamily: self::fontFamily($attributes['font_family'] ?? null) ?? self::DEFAULT_FONT,
            fontUrl: self::fontUrl($attributes['font_url'] ?? null),
            radius: self::length($attributes['radius'] ?? null, self::MIN_RADIUS, self::MAX_RADIUS, self::DEFAULT_RADIUS),
            width: self::length($attributes['width'] ?? null, self::MIN_WIDTH, self::MAX_WIDTH, self::DEFAULT_WIDTH),
            brandColor: self::color($attributes['brand_color'] ?? null) ?? self::DEFAULT_BRAND,
            background: self::color($attributes['background'] ?? null),
            foreground: self::color($attributes['foreground'] ?? null),
            upColor: self::color($attributes['up_color'] ?? null) ?? self::DEFAULT_UP,
            downColor: self::color($attributes['down_color'] ?? null) ?? self::DEFAULT_DOWN,
            warningColor: self::color($attributes['warning_color'] ?? null) ?? self::DEFAULT_WARNING,
            logoUrl: self::url($attributes['logo_url'] ?? null),
            faviconUrl: self::url($attributes['favicon_url'] ?? null),
            footerText: self::text($attributes['footer_text'] ?? null),
            links: self::links($attributes['links'] ?? null),
        );
    }

    public static function default(): self
    {
        return self::fromArray(null);
    }

    /**
     * The shape stored on the model and handed to the form. Always complete,
     * so the editor never has to reason about missing keys.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode->value,
            'font_family' => $this->fontFamily,
            'font_url' => $this->fontUrl,
            'radius' => $this->radius,
            'width' => $this->width,
            'brand_color' => $this->brandColor,
            'background' => $this->background,
            'foreground' => $this->foreground,
            'up_color' => $this->upColor,
            'down_color' => $this->downColor,
            'warning_color' => $this->warningColor,
            'logo_url' => $this->logoUrl,
            'favicon_url' => $this->faviconUrl,
            'footer_text' => $this->footerText,
            'links' => $this->links,
        ];
    }

    /**
     * The custom properties for one colour scheme.
     *
     * A custom background wins in both schemes: someone who has typed their
     * brand's off-white means it, and silently discarding it in dark mode
     * would look like the setting had not saved.
     *
     * @return array<string, string>
     */
    public function palette(StatusPageMode $scheme): array
    {
        $isDarkScheme = $scheme === StatusPageMode::Dark;

        $background = Color::parse($this->background)
            ?? Color::parse($isDarkScheme ? self::DARK_BACKGROUND : self::LIGHT_BACKGROUND);

        $foreground = Color::parse($this->foreground)
            ?? ($this->background !== null
                ? $background->readableText()
                : Color::parse($isDarkScheme ? self::DARK_FOREGROUND : self::LIGHT_FOREGROUND));

        $brand = (Color::parse($this->brandColor) ?? Color::parse(self::DEFAULT_BRAND))
            ->legibleOn($background);

        return [
            '--sp-bg' => $background->toHex(),
            '--sp-fg' => $foreground->toHex(),
            // Cards sit a touch off the page plane rather than being outlined,
            // matching how the rest of the app treats raised surfaces.
            '--sp-surface' => $background->mix($foreground, 0.04)->toHex(),
            '--sp-muted' => $background->mix($foreground, 0.07)->toHex(),
            '--sp-muted-fg' => $foreground->mix($background, 0.42)->toHex(),
            '--sp-border' => $background->mix($foreground, 0.16)->toHex(),
            '--sp-brand' => $brand->toHex(),
            '--sp-brand-fg' => $brand->readableText()->toHex(),
            '--sp-up' => self::statusColor($this->upColor, self::DEFAULT_UP, $background),
            '--sp-down' => self::statusColor($this->downColor, self::DEFAULT_DOWN, $background),
            '--sp-warning' => self::statusColor($this->warningColor, self::DEFAULT_WARNING, $background),
            '--sp-empty' => $background->mix($foreground, 0.13)->toHex(),
            '--sp-radius' => $this->radius.'px',
            '--sp-font' => $this->fontFamily,
            '--sp-width' => $this->width.'px',
            // Not a custom property: this is what makes scrollbars and native
            // controls follow a custom background rather than the OS default.
            'color-scheme' => $background->isDark() ? 'dark' : 'light',
        ];
    }

    /**
     * The stylesheet the public page renders inline.
     *
     * `system` becomes a media query rather than a class the client toggles on
     * hydration — the page is server-rendered, and a class toggle would show
     * the light palette for a frame before correcting itself.
     */
    public function css(string $selector = '.sp-theme'): string
    {
        $base = $this->mode === StatusPageMode::Dark
            ? StatusPageMode::Dark
            : StatusPageMode::Light;

        $css = $this->fontFace().$this->block($selector, $this->palette($base));

        if ($this->mode === StatusPageMode::System) {
            $css .= '@media (prefers-color-scheme:dark){'
                .$this->block($selector, $this->palette(StatusPageMode::Dark))
                .'}';
        }

        return $css;
    }

    /**
     * The `@font-face` for a self-hosted corporate typeface, named after the
     * first family in the stack so `--sp-font` picks it up without the owner
     * having to repeat themselves.
     */
    private function fontFace(): string
    {
        if ($this->fontUrl === null) {
            return '';
        }

        $family = self::primaryFamily($this->fontFamily);
        $extension = strtolower(pathinfo((string) parse_url($this->fontUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        $format = self::FONT_FORMATS[$extension] ?? null;

        if ($family === null || $format === null) {
            return '';
        }

        return "@font-face{font-family:'".$family."';"
            .'src:url("'.$this->fontUrl.'") format("'.$format.'");'
            .'font-display:swap;}';
    }

    /**
     * @param  array<string, string>  $palette
     */
    private function block(string $selector, array $palette): string
    {
        $declarations = '';

        foreach ($palette as $property => $value) {
            $declarations .= $property.':'.$value.';';
        }

        return $selector.'{'.$declarations.'}';
    }

    private static function statusColor(string $value, string $fallback, Color $background): string
    {
        return (Color::parse($value) ?? Color::parse($fallback))->legibleOn($background)->toHex();
    }

    private static function mode(mixed $value): StatusPageMode
    {
        return (is_string($value) ? StatusPageMode::tryFrom($value) : null) ?? StatusPageMode::System;
    }

    private static function color(mixed $value): ?string
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
    private static function fontFamily(mixed $value): ?string
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
    private static function primaryFamily(string $stack): ?string
    {
        $first = trim((string) strtok($stack, ','), " \t\n\r\0\x0B\"'");

        return $first === '' ? null : $first;
    }

    /**
     * A font file we can build an `@font-face` around. Quotes, parentheses,
     * backslashes, angle brackets and whitespace are rejected outright rather
     * than escaped: the URL is emitted inside `url("…")`, and dropping `<`
     * and `>` is what guarantees {@see css()} can never produce a `</style>`
     * for the page to render unescaped.
     */
    private static function fontUrl(mixed $value): ?string
    {
        $url = self::url($value);

        if ($url === null || preg_match('/["\'()<>\\\\\s]/', $url) === 1) {
            return null;
        }

        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return isset(self::FONT_FORMATS[$extension]) ? $url : null;
    }

    private static function length(mixed $value, int $min, int $max, int $fallback): int
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
    private static function url(mixed $value): ?string
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

    private static function text(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, 255);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private static function links(mixed $value): array
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
