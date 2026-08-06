<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An RFC 7230 header name that is safe to let a user set.
 *
 * The denylist matters more than the character check. Overriding Host is a
 * request-smuggling and SSRF-pivot primitive: it reaches a different virtual
 * host on an address that passed the guard. The rest — Content-Length,
 * Transfer-Encoding, Connection, Upgrade, Expect, and the proxy headers —
 * control the client's own framing, so setting them corrupts the request
 * rather than the target.
 */
class HttpHeaderName implements ValidationRule
{
    private const DENIED = [
        'host',
        'content-length',
        'transfer-encoding',
        'connection',
        'upgrade',
        'expect',
    ];

    private const TOKEN = "/^[A-Za-z0-9!#$%&'*+\-.^_`|~]+$/";

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($message = self::reject($value)) {
            $fail($message)->translate(['name' => is_string($value) ? $value : '']);
        }
    }

    /**
     * The check itself, callable without a validator.
     *
     * Header names arrive as array *keys*, which Laravel's rule pipeline
     * cannot address, so MonitorRequest checks them in an after() hook and
     * needs the verdict rather than a callback.
     *
     * @return string|null A translation key, or null when the name is fine.
     */
    public static function reject(mixed $value): ?string
    {
        $name = is_string($value) ? strtolower(trim($value)) : '';

        if ($name === '' || ! preg_match(self::TOKEN, $name)) {
            return 'validation.header_name';
        }

        if (in_array($name, self::DENIED, true) || str_starts_with($name, 'proxy-')) {
            return 'validation.header_name_reserved';
        }

        return null;
    }
}
