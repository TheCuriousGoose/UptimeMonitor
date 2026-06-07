<?php

namespace App\Checkers;

final readonly class CheckResult
{
    public function __construct(
        public readonly bool $isUp,
        public readonly int $responseMs,
        public readonly ?string $error = null,
        public readonly array $meta = [],
    ) {}

    public static function up(int $responseMs, array $meta = []): self
    {
        return new self(true, $responseMs, null, $meta);
    }

    public static function down(string $error, int $responseMs = 0, array $meta = []): self
    {
        return new self(false, $responseMs, $error, $meta);
    }
}
