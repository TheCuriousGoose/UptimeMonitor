<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Resolves a stringly-typed discriminator to the class that handles it.
 *
 * Both the checker and notifier registries were this class, written twice,
 * differing only in the noun they put in the error message. Subclasses narrow
 * the return type so callers keep a real interface rather than `object`.
 *
 * @template T of object
 */
abstract class TypeRegistry
{
    /**
     * @param  array<string, class-string<T>>  $map
     */
    public function __construct(private readonly array $map) {}

    /**
     * The noun used when a type has no handler, e.g. "monitor type".
     */
    abstract protected function label(): string;

    public function has(string $type): bool
    {
        return isset($this->map[$type]);
    }

    /**
     * @return array<int, string>
     */
    public function types(): array
    {
        return array_keys($this->map);
    }

    /**
     * @return T
     */
    protected function make(string $type): object
    {
        if (! $this->has($type)) {
            throw new InvalidArgumentException("Unknown {$this->label()}: [{$type}]");
        }

        return app($this->map[$type]);
    }
}
