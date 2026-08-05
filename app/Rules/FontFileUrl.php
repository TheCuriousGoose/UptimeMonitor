<?php

namespace App\Rules;

use App\StatusPages\StatusPageTheme;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A URL pointing at a webfont file we can wrap in our own `@font-face`.
 *
 * Deliberately not a stylesheet URL: the point of accepting a font at all is
 * "use our corporate typeface", and a remote CSS file would instead hand a
 * third-party host authorship of a public page on this domain.
 */
class FontFileUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $url = trim($value);

        // The URL is emitted inside `url("…")`, so anything that could close
        // that function is rejected rather than escaped.
        if (preg_match('/["\'()\\\\\s]/', $url) === 1) {
            $fail('status_pages.validation.font_url')->translate();

            return;
        }

        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        if (! isset(StatusPageTheme::FONT_FORMATS[$extension])) {
            $fail('status_pages.validation.font_url')->translate([
                'formats' => implode(', ', array_keys(StatusPageTheme::FONT_FORMATS)),
            ]);
        }
    }
}
