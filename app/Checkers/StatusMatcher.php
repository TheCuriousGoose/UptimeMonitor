<?php

namespace App\Checkers;

/**
 * Decides whether a response status counts as healthy.
 *
 * Parsing lives here rather than in the checker so that PHP and the monitor
 * form cannot disagree about what "2xx, 301-304" means.
 *
 * Accepted patterns: an exact code ("204"), an inclusive range ("200-299"),
 * or a class ("2xx"). An empty set means "anything below 400", which is what
 * a monitor with no expectation configured has always done.
 */
final readonly class StatusMatcher
{
    /**
     * @param  array<int, string>  $patterns
     */
    private function __construct(private array $patterns) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $codes = $config['expected_status_codes'] ?? [];

        if (is_array($codes) && $codes !== []) {
            return new self(array_values(array_filter(
                array_map(fn ($code) => trim((string) $code), $codes),
                fn (string $code) => $code !== '',
            )));
        }

        // Monitors created before ranges existed still carry a single int, and
        // API clients that have not been updated still post one.
        $legacy = $config['expected_status'] ?? null;

        if ($legacy !== null && $legacy !== '') {
            return new self([(string) (int) $legacy]);
        }

        return new self([]);
    }

    public function matches(int $status): bool
    {
        if ($this->patterns === []) {
            return $status < 400;
        }

        foreach ($this->patterns as $pattern) {
            if ($this->patternMatches($pattern, $status)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the user configured anything, as opposed to the implicit
     * "below 400". Drives the wording of the failure message.
     */
    public function isExplicit(): bool
    {
        return $this->patterns !== [];
    }

    /**
     * How the expectation reads in an error message.
     */
    public function describe(): string
    {
        return $this->patterns === []
            ? 'a status below 400'
            : 'HTTP '.implode(', ', $this->patterns);
    }

    private function patternMatches(string $pattern, int $status): bool
    {
        if (preg_match('/^(\d{3})$/', $pattern, $m)) {
            return $status === (int) $m[1];
        }

        if (preg_match('/^(\d{3})-(\d{3})$/', $pattern, $m)) {
            return $status >= (int) $m[1] && $status <= (int) $m[2];
        }

        if (preg_match('/^([1-5])xx$/i', $pattern, $m)) {
            $floor = (int) $m[1] * 100;

            return $status >= $floor && $status < $floor + 100;
        }

        return false;
    }
}
