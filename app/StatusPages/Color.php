<?php

namespace App\StatusPages;

/**
 * The small amount of colour maths a status page theme needs.
 *
 * A page owner picks two or three colours; everything else on the page — card
 * surfaces, hairline borders, dimmed secondary text — is mixed from those so
 * the result stays coherent whatever they choose. Deriving the neutrals is
 * what stops a custom background from leaving unreadable grey text behind.
 */
final class Color
{
    /**
     * @param  int  $r  0-255
     * @param  int  $g  0-255
     * @param  int  $b  0-255
     */
    private function __construct(
        public readonly int $r,
        public readonly int $g,
        public readonly int $b,
    ) {}

    /**
     * Accepts `#abc` and `#abcdef`, with or without the hash. Returns null for
     * anything else rather than guessing — callers fall back to a default.
     */
    public static function parse(?string $hex): ?self
    {
        if (! is_string($hex)) {
            return null;
        }

        $value = ltrim(trim($hex), '#');

        if (preg_match('/^[0-9a-fA-F]{3}$/', $value) === 1) {
            $value = $value[0].$value[0].$value[1].$value[1].$value[2].$value[2];
        }

        if (preg_match('/^[0-9a-fA-F]{6}$/', $value) !== 1) {
            return null;
        }

        return new self(
            (int) hexdec(substr($value, 0, 2)),
            (int) hexdec(substr($value, 2, 2)),
            (int) hexdec(substr($value, 4, 2)),
        );
    }

    public function toHex(): string
    {
        return sprintf('#%02x%02x%02x', $this->r, $this->g, $this->b);
    }

    /**
     * @param  float  $weight  0 keeps this colour, 1 becomes $other.
     */
    public function mix(self $other, float $weight): self
    {
        $weight = max(0.0, min(1.0, $weight));

        return new self(
            (int) round($this->r + ($other->r - $this->r) * $weight),
            (int) round($this->g + ($other->g - $this->g) * $weight),
            (int) round($this->b + ($other->b - $this->b) * $weight),
        );
    }

    /**
     * WCAG relative luminance, used to decide light-on-dark versus dark-on-light.
     */
    public function luminance(): float
    {
        $channel = function (int $value): float {
            $srgb = $value / 255;

            return $srgb <= 0.03928
                ? $srgb / 12.92
                : (($srgb + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel($this->r)
            + 0.7152 * $channel($this->g)
            + 0.0722 * $channel($this->b);
    }

    public function isDark(): bool
    {
        return $this->luminance() < 0.4;
    }

    /**
     * Text that stays legible on this colour. Near-black rather than pure black
     * so a light brand colour does not read as a hard, printed-looking label.
     */
    public function readableText(): self
    {
        return $this->isDark()
            ? new self(255, 255, 255)
            : new self(17, 17, 20);
    }

    /**
     * Pull a colour toward the middle of the range so it stays visible against
     * its own background — a near-black brand colour on a dark page, or a
     * near-white one on a light page, would otherwise vanish.
     */
    public function legibleOn(self $background): self
    {
        $backgroundIsDark = $background->isDark();

        if ($backgroundIsDark && $this->luminance() < 0.12) {
            return $this->mix(new self(255, 255, 255), 0.55);
        }

        if (! $backgroundIsDark && $this->luminance() > 0.75) {
            return $this->mix(new self(0, 0, 0), 0.4);
        }

        return $this;
    }
}
