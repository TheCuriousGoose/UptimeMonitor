<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A header value with no line breaks in it.
 *
 * A CR or LF here lets the value close the header and start another one, so
 * a single "X-Foo" field could inject a Host or a whole second request. This
 * is checked rather than left to the HTTP client to reject, because being
 * refused at save time is a far better experience than a monitor that fails
 * every check with an opaque cURL error.
 */
class HttpHeaderValue implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/[\r\n\0]/', $value)) {
            $fail('validation.header_value')->translate();
        }
    }
}
