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

    public const MAX_LINKS = ThemeValues::MAX_LINKS;

    public const FONT_FORMATS = ThemeValues::FONT_FORMATS;

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
            mode: ThemeValues::mode($attributes['mode'] ?? null),
            fontFamily: ThemeValues::fontFamily($attributes['font_family'] ?? null) ?? self::DEFAULT_FONT,
            fontUrl: ThemeValues::fontUrl($attributes['font_url'] ?? null),
            radius: ThemeValues::length($attributes['radius'] ?? null, self::MIN_RADIUS, self::MAX_RADIUS, self::DEFAULT_RADIUS),
            width: ThemeValues::length($attributes['width'] ?? null, self::MIN_WIDTH, self::MAX_WIDTH, self::DEFAULT_WIDTH),
            brandColor: ThemeValues::color($attributes['brand_color'] ?? null) ?? self::DEFAULT_BRAND,
            background: ThemeValues::color($attributes['background'] ?? null),
            foreground: ThemeValues::color($attributes['foreground'] ?? null),
            upColor: ThemeValues::color($attributes['up_color'] ?? null) ?? self::DEFAULT_UP,
            downColor: ThemeValues::color($attributes['down_color'] ?? null) ?? self::DEFAULT_DOWN,
            warningColor: ThemeValues::color($attributes['warning_color'] ?? null) ?? self::DEFAULT_WARNING,
            logoUrl: ThemeValues::url($attributes['logo_url'] ?? null),
            faviconUrl: ThemeValues::url($attributes['favicon_url'] ?? null),
            footerText: ThemeValues::text($attributes['footer_text'] ?? null),
            links: ThemeValues::links($attributes['links'] ?? null),
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
     */
    public function css(string $selector = '.sp-theme'): string
    {
        return (new ThemeStylesheet($this))->render($selector);
    }

    private static function statusColor(string $value, string $fallback, Color $background): string
    {
        return (Color::parse($value) ?? Color::parse($fallback))->legibleOn($background)->toHex();
    }
}
