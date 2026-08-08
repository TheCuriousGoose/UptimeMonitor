<?php

namespace App\Monitoring\Notifiers;

use App\Support\TypeRegistry;

/**
 * @extends TypeRegistry<Notifier>
 */
class NotifierRegistry extends TypeRegistry
{
    public function resolve(string $type): Notifier
    {
        return $this->make($type);
    }

    protected function label(): string
    {
        return 'notification channel type';
    }
}
