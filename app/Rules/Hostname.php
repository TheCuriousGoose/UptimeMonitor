<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts a bare hostname, an IPv4/IPv6 literal, or either with a :port suffix.
 */
class Hostname implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('validation.hostname')->translate();

            return;
        }

        $host = trim($value);

        if (str_contains($host, '://')) {
            $fail('validation.hostname')->translate();

            return;
        }

        // Strip a trailing :port when the remainder is not an IPv6 literal.
        if (substr_count($host, ':') === 1) {
            [$candidate, $port] = explode(':', $host, 2);

            if (ctype_digit($port)) {
                $host = $candidate;
            }
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return;
        }

        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) && str_contains($host, '.')) {
            return;
        }

        $fail('validation.hostname')->translate();
    }
}
