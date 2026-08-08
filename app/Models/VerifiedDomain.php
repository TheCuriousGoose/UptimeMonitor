<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A claim that a user owns a registrable domain, proven by publishing a token
 * they cannot write without control of the domain.
 */
#[Fillable(['user_id', 'domain', 'token', 'verified_at', 'last_error', 'last_attempted_at'])]
class VerifiedDomain extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'verified_at' => 'immutable_datetime',
            'last_attempted_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (VerifiedDomain $domain): void {
            $domain->token ??= 'vigil-verify='.Str::random(40);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    #[Scope]
    protected function verified(Builder $query): void
    {
        $query->whereNotNull('verified_at');
    }
}
