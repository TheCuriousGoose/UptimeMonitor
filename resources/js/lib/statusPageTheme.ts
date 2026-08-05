import type { StatusPageMode, StatusPageTheme } from '@/types/monitors';

/**
 * A client-side port of App\StatusPages\StatusPageTheme::palette(), used only
 * to render the live preview while somebody is editing.
 *
 * The PHP is the source of truth — it is what the published page is built
 * from, and StatusPageThemeTest pins its output. This exists because the
 * alternative is a server round-trip per keystroke; if the two ever drift, the
 * preview is a shade off, which is a cosmetic bug rather than a broken page.
 */

export const THEME_DEFAULTS: StatusPageTheme = {
    mode: 'system',
    font_family: "'Instrument Sans', ui-sans-serif, system-ui, sans-serif",
    font_url: null,
    radius: 4,
    width: 768,
    brand_color: '#4f46e5',
    background: null,
    foreground: null,
    up_color: '#0ca30c',
    down_color: '#d03b3b',
    warning_color: '#fab219',
    logo_url: null,
    favicon_url: null,
    footer_text: null,
    links: [],
};

/** Mirrors the bounds enforced by StatusPageRequest, for the editor's inputs. */
export const THEME_LIMITS = {
    radius: { min: 0, max: 32 },
    width: { min: 320, max: 1600 },
    links: 5,
} as const;

const LIGHT_BACKGROUND = '#ffffff';
const LIGHT_FOREGROUND = '#111114';
const DARK_BACKGROUND = '#0b0b0f';
const DARK_FOREGROUND = '#f4f4f6';

type Rgb = { r: number; g: number; b: number };

export function parseHex(value: string | null | undefined): Rgb | null {
    if (typeof value !== 'string') {
        return null;
    }

    let hex = value.trim().replace(/^#/, '');

    if (/^[0-9a-f]{3}$/i.test(hex)) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }

    if (!/^[0-9a-f]{6}$/i.test(hex)) {
        return null;
    }

    return {
        r: parseInt(hex.slice(0, 2), 16),
        g: parseInt(hex.slice(2, 4), 16),
        b: parseInt(hex.slice(4, 6), 16),
    };
}

function toHex({ r, g, b }: Rgb): string {
    const channel = (value: number) =>
        Math.round(value).toString(16).padStart(2, '0');

    return `#${channel(r)}${channel(g)}${channel(b)}`;
}

function mix(base: Rgb, other: Rgb, weight: number): Rgb {
    const amount = Math.max(0, Math.min(1, weight));

    return {
        r: Math.round(base.r + (other.r - base.r) * amount),
        g: Math.round(base.g + (other.g - base.g) * amount),
        b: Math.round(base.b + (other.b - base.b) * amount),
    };
}

function luminance({ r, g, b }: Rgb): number {
    const channel = (value: number) => {
        const srgb = value / 255;

        return srgb <= 0.03928 ? srgb / 12.92 : ((srgb + 0.055) / 1.055) ** 2.4;
    };

    return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
}

export function isDark(color: Rgb): boolean {
    return luminance(color) < 0.4;
}

function readableText(color: Rgb): Rgb {
    return isDark(color) ? { r: 255, g: 255, b: 255 } : { r: 17, g: 17, b: 20 };
}

function legibleOn(color: Rgb, background: Rgb): Rgb {
    const backgroundIsDark = isDark(background);

    if (backgroundIsDark && luminance(color) < 0.12) {
        return mix(color, { r: 255, g: 255, b: 255 }, 0.55);
    }

    if (!backgroundIsDark && luminance(color) > 0.75) {
        return mix(color, { r: 0, g: 0, b: 0 }, 0.4);
    }

    return color;
}

function fallback(value: string | null | undefined, backup: string): Rgb {
    return parseHex(value) ?? (parseHex(backup) as Rgb);
}

/**
 * The custom properties one colour scheme resolves to. Mirrors the PHP so the
 * preview and the published page agree.
 */
export function palette(
    theme: StatusPageTheme,
    scheme: Exclude<StatusPageMode, 'system'>,
): Record<string, string> {
    const schemeIsDark = scheme === 'dark';

    const background =
        parseHex(theme.background) ??
        (parseHex(schemeIsDark ? DARK_BACKGROUND : LIGHT_BACKGROUND) as Rgb);

    const foreground =
        parseHex(theme.foreground) ??
        (theme.background
            ? readableText(background)
            : (parseHex(
                  schemeIsDark ? DARK_FOREGROUND : LIGHT_FOREGROUND,
              ) as Rgb));

    const brand = legibleOn(
        fallback(theme.brand_color, THEME_DEFAULTS.brand_color),
        background,
    );

    const status = (value: string | null, backup: string) =>
        toHex(legibleOn(fallback(value, backup), background));

    return {
        '--sp-bg': toHex(background),
        '--sp-fg': toHex(foreground),
        '--sp-surface': toHex(mix(background, foreground, 0.04)),
        '--sp-muted': toHex(mix(background, foreground, 0.07)),
        '--sp-muted-fg': toHex(mix(foreground, background, 0.42)),
        '--sp-border': toHex(mix(background, foreground, 0.16)),
        '--sp-brand': toHex(brand),
        '--sp-brand-fg': toHex(readableText(brand)),
        '--sp-up': status(theme.up_color, THEME_DEFAULTS.up_color),
        '--sp-down': status(theme.down_color, THEME_DEFAULTS.down_color),
        '--sp-warning': status(
            theme.warning_color,
            THEME_DEFAULTS.warning_color,
        ),
        '--sp-empty': toHex(mix(background, foreground, 0.13)),
        '--sp-radius': `${theme.radius}px`,
        '--sp-font': theme.font_family || THEME_DEFAULTS.font_family,
        '--sp-width': `${theme.width}px`,
        'color-scheme': isDark(background) ? 'dark' : 'light',
    };
}

/**
 * Which scheme the preview should show. `system` has no single answer, so it
 * follows whatever the person editing is currently looking at.
 */
export function previewScheme(
    mode: StatusPageMode,
    prefersDark: boolean,
): Exclude<StatusPageMode, 'system'> {
    if (mode === 'system') {
        return prefersDark ? 'dark' : 'light';
    }

    return mode;
}
