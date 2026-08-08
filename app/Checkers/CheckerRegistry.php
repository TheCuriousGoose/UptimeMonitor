<?php

namespace App\Checkers;

use App\Support\TypeRegistry;

/**
 * @extends TypeRegistry<Checker>
 */
class CheckerRegistry extends TypeRegistry
{
    public function resolve(string $type): Checker
    {
        return $this->make($type);
    }

    protected function label(): string
    {
        return 'monitor type';
    }
}
