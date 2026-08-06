<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * A JSON column held encrypted at rest.
 *
 * Reads fall back to plain json_decode when decryption fails, which is what
 * makes a zero-downtime deploy possible: between the schema change and the
 * last row the backfill rewrites, workers still see plaintext. Without the
 * fallback they raise DecryptException — and RunMonitorCheck has tries = 1
 * with no failed() handler, so that check is simply lost.
 *
 * The fallback can be deleted one release after the encrypt_config_columns
 * migration has run everywhere.
 *
 * @implements CastsAttributes<array<string, mixed>|null, array<string, mixed>|null>
 */
class EncryptedJson implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $value = Crypt::decryptString($value);
        } catch (DecryptException) {
            // Still plaintext from before the backfill — see the class docblock.
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString(json_encode($value));
    }
}
