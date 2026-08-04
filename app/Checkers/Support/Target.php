<?php

namespace App\Checkers\Support;

/**
 * Normalises a monitor target into the host / port pieces the
 * non-HTTP checkers need, accepting either a bare hostname or a URL.
 */
final readonly class Target
{
    public function __construct(
        public string $host,
        public ?int $port = null,
        public string $scheme = '',
    ) {}

    public static function parse(string $target): self
    {
        $target = trim($target);

        if (! str_contains($target, '://')) {
            // "example.com:8080" — but leave bare IPv6 literals alone.
            if (substr_count($target, ':') === 1) {
                [$host, $port] = explode(':', $target, 2);

                if (ctype_digit($port)) {
                    return new self($host, (int) $port);
                }
            }

            return new self(rtrim($target, '/'));
        }

        $parts = parse_url($target);

        return new self(
            host: $parts['host'] ?? '',
            port: isset($parts['port']) ? (int) $parts['port'] : null,
            scheme: $parts['scheme'] ?? '',
        );
    }

    public function portOr(int $default): int
    {
        return $this->port ?? $default;
    }
}
