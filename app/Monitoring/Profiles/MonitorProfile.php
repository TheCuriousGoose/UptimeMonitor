<?php

namespace App\Monitoring\Profiles;

use App\Checkers\Checker;

/**
 * Everything that varies between monitor types, in one place per type.
 *
 * These four facts used to live in four unrelated tables — validation rules
 * and defaults on the enum, the cast for the very same keys hardcoded in
 * MonitorRequest, and the checker class in AppServiceProvider. Adding a
 * config field meant finding all of them, and nothing failed if you missed
 * the cast table.
 */
interface MonitorProfile
{
    /**
     * Whether the target is a full URL rather than a bare hostname.
     */
    public function expectsUrl(): bool;

    /**
     * @return class-string<Checker>
     */
    public function checker(): string;

    /**
     * Validation rules for the type specific `config` payload.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array;

    /**
     * Values applied when the user leaves a config field untouched. Also the
     * allowlist: a submitted key absent from here is discarded on save.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array;

    /**
     * @return array<string, ConfigCast>
     */
    public function casts(): array;
}
