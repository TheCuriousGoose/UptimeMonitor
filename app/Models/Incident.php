<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['monitor_id', 'started_at', 'resolved_at', 'cause', 'failed_checks'])]
class Incident extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
            'failed_checks' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function isOngoing(): bool
    {
        return $this->resolved_at === null;
    }

    /**
     * Length of the outage in seconds; for an open incident, how long it has run so far.
     */
    public function durationSeconds(): int
    {
        return (int) $this->started_at->diffInSeconds($this->resolved_at ?? now());
    }

    public function scopeOngoing(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }
}
