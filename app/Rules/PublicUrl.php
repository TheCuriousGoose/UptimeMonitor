<?php

namespace App\Rules;

use App\Checkers\Support\OutboundGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/**
 * A URL whose hostname resolves to at least one public address.
 *
 * Applied only when a monitor carries credentials — request headers, a body,
 * or auth. Monitoring a private host stays allowed; sending secrets to one is
 * what this refuses, because the app cannot tell an internal service the user
 * owns from one they are probing.
 */
class PublicUrl implements ValidationRule
{
    public function __construct(private readonly OutboundGuard $guard) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->allows($value)) {
            $fail('validation.public_url')->translate();
        }
    }

    /**
     * The check itself, callable without a validator — MonitorRequest decides
     * whether this rule applies at all from other fields, so it runs the test
     * from an after() hook rather than the rule pipeline.
     */
    public function allows(mixed $value): bool
    {
        if (! is_string($value) || $value === '') {
            return true;
        }

        try {
            $this->guard->resolve($value, requirePublic: true);

            return true;
        } catch (Throwable) {
            // Includes the unresolvable case: a name that does not resolve
            // today could resolve to a private address tomorrow.
            return false;
        }
    }
}
