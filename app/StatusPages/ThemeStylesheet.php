<?php

namespace App\StatusPages;

use App\Enums\StatusPageMode;

/**
 * Writes a theme out as the inline stylesheet the public page renders.
 *
 * Separate from the theme itself because emitting CSS is where the safety
 * argument actually has to hold: every value reaching this class has already
 * been normalised by {@see ThemeValues}, and nothing here re-opens that by
 * interpolating something it was handed raw.
 */
final readonly class ThemeStylesheet
{
    public function __construct(private StatusPageTheme $theme) {}

    /**
     * `system` becomes a media query rather than a class the client toggles on
     * hydration — the page is server-rendered, and a class toggle would show
     * the light palette for a frame before correcting itself.
     */
    public function render(string $selector = '.sp-theme'): string
    {
        $base = $this->theme->mode === StatusPageMode::Dark
            ? StatusPageMode::Dark
            : StatusPageMode::Light;

        $css = $this->fontFace().$this->block($selector, $this->theme->palette($base));

        if ($this->theme->mode === StatusPageMode::System) {
            $css .= '@media (prefers-color-scheme:dark){'
                .$this->block($selector, $this->theme->palette(StatusPageMode::Dark))
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
        if ($this->theme->fontUrl === null) {
            return '';
        }

        $family = ThemeValues::primaryFamily($this->theme->fontFamily);
        $format = ThemeValues::fontFormat($this->theme->fontUrl);

        if ($family === null || $format === null) {
            return '';
        }

        return "@font-face{font-family:'".$family."';"
            .'src:url("'.$this->theme->fontUrl.'") format("'.$format.'");'
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
}
