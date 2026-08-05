<?php

namespace Tests\Unit\StatusPages;

use App\Enums\StatusPageMode;
use App\StatusPages\StatusPageTheme;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StatusPageThemeTest extends TestCase
{
    public function test_a_page_without_a_theme_gets_the_defaults(): void
    {
        $theme = StatusPageTheme::default()->toArray();

        $this->assertSame(StatusPageMode::System->value, $theme['mode']);
        $this->assertSame(StatusPageTheme::DEFAULT_BRAND, $theme['brand_color']);
        $this->assertSame(StatusPageTheme::DEFAULT_FONT, $theme['font_family']);
        $this->assertSame(StatusPageTheme::DEFAULT_RADIUS, $theme['radius']);
        $this->assertSame(StatusPageTheme::DEFAULT_WIDTH, $theme['width']);
        $this->assertNull($theme['background']);
        $this->assertSame([], $theme['links']);
    }

    public function test_a_partial_theme_keeps_what_was_set_and_fills_the_rest(): void
    {
        $theme = StatusPageTheme::fromArray(['brand_color' => '#F06'])->toArray();

        $this->assertSame('#ff0066', $theme['brand_color']);
        $this->assertSame(StatusPageTheme::DEFAULT_UP, $theme['up_color']);
    }

    /**
     * Surfaces, borders and dimmed text are mixed from the two colours the
     * owner actually chose, so the derived neutrals move with them.
     */
    public function test_it_derives_the_neutrals_from_background_and_text(): void
    {
        $palette = StatusPageTheme::default()->palette(StatusPageMode::Light);

        $this->assertSame('#ffffff', $palette['--sp-bg']);
        $this->assertSame('#111114', $palette['--sp-fg']);
        $this->assertSame('#f5f5f6', $palette['--sp-surface']);
        $this->assertSame('#4f46e5', $palette['--sp-brand']);
        $this->assertSame('#ffffff', $palette['--sp-brand-fg']);
        $this->assertSame('4px', $palette['--sp-radius']);
        $this->assertSame('768px', $palette['--sp-width']);
        $this->assertSame('light', $palette['color-scheme']);
    }

    public function test_a_dark_scheme_flips_the_defaults_and_the_color_scheme(): void
    {
        $palette = StatusPageTheme::fromArray(['mode' => 'dark'])->palette(StatusPageMode::Dark);

        $this->assertSame('#0b0b0f', $palette['--sp-bg']);
        $this->assertSame('#f4f4f6', $palette['--sp-fg']);
        $this->assertSame('dark', $palette['color-scheme']);
    }

    /**
     * A custom background applies to both schemes, so the text colour has to be
     * chosen from the background rather than from the scheme.
     */
    public function test_a_custom_background_picks_its_own_text_colour(): void
    {
        $dark = StatusPageTheme::fromArray(['background' => '#101820'])
            ->palette(StatusPageMode::Light);

        $this->assertSame('#101820', $dark['--sp-bg']);
        $this->assertSame('#ffffff', $dark['--sp-fg']);
        $this->assertSame('dark', $dark['color-scheme']);

        $light = StatusPageTheme::fromArray(['background' => '#fdf6e3'])
            ->palette(StatusPageMode::Dark);

        $this->assertSame('#111114', $light['--sp-fg']);
        $this->assertSame('light', $light['color-scheme']);
    }

    public function test_a_brand_colour_that_would_vanish_is_pulled_back(): void
    {
        $palette = StatusPageTheme::fromArray([
            'background' => '#000000',
            'brand_color' => '#020202',
        ])->palette(StatusPageMode::Dark);

        $this->assertNotSame('#020202', $palette['--sp-brand']);
        $this->assertSame('#8d8d8d', $palette['--sp-brand']);
    }

    public function test_system_mode_emits_a_media_query_and_the_fixed_modes_do_not(): void
    {
        $this->assertStringContainsString(
            '@media (prefers-color-scheme:dark)',
            StatusPageTheme::fromArray(['mode' => 'system'])->css(),
        );

        $this->assertStringNotContainsString(
            '@media',
            StatusPageTheme::fromArray(['mode' => 'dark'])->css(),
        );

        $this->assertStringNotContainsString(
            '@media',
            StatusPageTheme::fromArray(['mode' => 'light'])->css(),
        );
    }

    public function test_the_stylesheet_is_scoped_to_the_page_wrapper(): void
    {
        $css = StatusPageTheme::fromArray(['mode' => 'light'])->css('.sp-theme');

        $this->assertStringStartsWith('.sp-theme{', $css);
    }

    /**
     * The stylesheet is rendered into a <style> element on an unauthenticated
     * page, so nothing an owner types may reach it as markup or as CSS of their
     * own. Angle brackets in particular must never survive: one `</style>`
     * would turn a theme field into stored XSS.
     */
    #[DataProvider('hostileThemes')]
    public function test_nothing_typed_into_a_theme_can_write_css_of_its_own(array $attributes): void
    {
        $css = StatusPageTheme::fromArray($attributes)->css();

        $this->assertStringNotContainsString('<', $css);
        $this->assertStringNotContainsString('>', $css);
        $this->assertStringNotContainsString('url(', $css);
        $this->assertStringNotContainsString('@import', $css);
        $this->assertStringNotContainsString('expression', $css);
    }

    public static function hostileThemes(): array
    {
        return [
            'closing style tag in the font stack' => [[
                'font_family' => 'Acme</style><script>alert(1)</script>',
            ]],
            'a rule of its own in the font stack' => [[
                'font_family' => "Acme'; } body { display: none } .x {",
            ]],
            'an import in the font stack' => [[
                'font_family' => '@import url(//evil.test/x.css);',
            ]],
            'a stylesheet posing as a font' => [[
                'font_url' => 'https://evil.test/fonts.css',
            ]],
            'a font url smuggling a second source' => [[
                'font_url' => 'https://evil.test/a.woff2") format("woff2"),url("//evil.test/b.woff2',
            ]],
            'colours that are not colours' => [[
                'brand_color' => 'red;} body{display:none}',
                'background' => 'expression(alert(1))',
            ]],
        ];
    }

    public function test_a_font_file_gets_a_font_face_bound_to_the_first_family(): void
    {
        $css = StatusPageTheme::fromArray([
            'font_family' => "'Acme Grotesk', Helvetica, sans-serif",
            'font_url' => 'https://acme.test/fonts/acme.woff2',
        ])->css();

        $this->assertStringStartsWith("@font-face{font-family:'Acme Grotesk';", $css);
        $this->assertStringContainsString('src:url("https://acme.test/fonts/acme.woff2") format("woff2");', $css);
        $this->assertStringContainsString('font-display:swap;', $css);
    }

    #[DataProvider('rejectedUrls')]
    public function test_it_only_keeps_absolute_http_urls(string $key, string $value): void
    {
        $theme = StatusPageTheme::fromArray([$key => $value])->toArray();

        $this->assertNull($theme[$key]);
    }

    public static function rejectedUrls(): array
    {
        return [
            'javascript logo' => ['logo_url', 'javascript:alert(1)'],
            'data logo' => ['logo_url', 'data:image/svg+xml,<svg onload=alert(1)>'],
            'relative logo' => ['logo_url', '/images/logo.svg'],
            'schemeless favicon' => ['favicon_url', '//acme.test/favicon.ico'],
            'stylesheet font' => ['font_url', 'https://acme.test/font.css'],
            'extensionless font' => ['font_url', 'https://acme.test/font'],
        ];
    }

    public function test_lengths_are_clamped_rather_than_rejected(): void
    {
        $huge = StatusPageTheme::fromArray(['radius' => 9999, 'width' => 99999])->toArray();

        $this->assertSame(StatusPageTheme::MAX_RADIUS, $huge['radius']);
        $this->assertSame(StatusPageTheme::MAX_WIDTH, $huge['width']);

        $tiny = StatusPageTheme::fromArray(['radius' => -40, 'width' => 10])->toArray();

        $this->assertSame(StatusPageTheme::MIN_RADIUS, $tiny['radius']);
        $this->assertSame(StatusPageTheme::MIN_WIDTH, $tiny['width']);
    }

    public function test_links_need_both_halves_and_stop_at_the_cap(): void
    {
        $links = StatusPageTheme::fromArray(['links' => [
            ['label' => 'Website', 'url' => 'https://acme.test'],
            ['label' => 'No URL'],
            ['url' => 'https://acme.test/no-label'],
            ['label' => 'Relative', 'url' => '/support'],
            ['label' => 'Docs', 'url' => 'https://acme.test/docs'],
        ]])->toArray()['links'];

        $this->assertSame([
            ['label' => 'Website', 'url' => 'https://acme.test'],
            ['label' => 'Docs', 'url' => 'https://acme.test/docs'],
        ], $links);
    }

    public function test_it_never_stores_more_links_than_the_cap(): void
    {
        $links = array_fill(0, StatusPageTheme::MAX_LINKS + 3, [
            'label' => 'Website',
            'url' => 'https://acme.test',
        ]);

        $this->assertCount(
            StatusPageTheme::MAX_LINKS,
            StatusPageTheme::fromArray(['links' => $links])->toArray()['links'],
        );
    }
}
