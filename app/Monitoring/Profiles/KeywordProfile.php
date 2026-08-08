<?php

namespace App\Monitoring\Profiles;

use App\Checkers\KeywordChecker;

/**
 * An HTTP check that also asserts on the response body, so it inherits the
 * whole HTTP surface — mirroring KeywordChecker extending HttpChecker.
 */
class KeywordProfile extends HttpProfile
{
    public function checker(): string
    {
        return KeywordChecker::class;
    }

    public function rules(): array
    {
        return parent::rules() + [
            'config.keyword' => ['required', 'string', 'max:255'],
            'config.invert' => ['sometimes', 'boolean'],
        ];
    }

    public function defaults(): array
    {
        return parent::defaults() + ['keyword' => '', 'invert' => false];
    }

    public function casts(): array
    {
        return parent::casts() + [
            'keyword' => ConfigCast::Raw,
            'invert' => ConfigCast::Bool,
        ];
    }
}
