<?php

namespace App\Models;

use App\Support\SqlDialect;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'monitor_id', 'started_at', 'resolved_at', 'cause', 'failed_checks',
    'is_maintenance', 'acknowledged_at', 'acknowledged_by',
])]
class Incident extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
            'is_maintenance' => 'boolean',
            'acknowledged_at' => 'immutable_datetime',
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

    public function notifications(): HasMany
    {
        return $this->hasMany(IncidentNotification::class);
    }

    /**
     * Whether anyone was ever told about this outage.
     *
     * The gate that keeps a suppressed incident quiet on both edges: an
     * outage nobody was told about must not produce a "recovered" alert out
     * of nowhere.
     */
    public function wasAnnounced(): bool
    {
        return $this->notifications()->where('notify_count', '>', 0)->exists();
    }

    public function updates()
    {
        return $this->hasMany(IncidentUpdate::class)->orderBy('created_at');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
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

    /**
     * The columns a client may order by, keyed by the name the table sends.
     *
     * An allowlist rather than a passthrough: `direction` is validated, but
     * the column would otherwise be interpolated straight into orderBy.
     */
    public const SORTS = [
        'monitor' => null,   // Lives on the related table — see sort().
        'status' => null,    // Derived from resolved_at being null.
        'duration' => null,  // Computed, not stored.
        'cause' => 'cause',
        'started' => 'started_at',
        'failed_checks' => 'failed_checks',
    ];

    /** Open incidents first — they are the ones that need someone. */
    private const RANK = 'CASE WHEN resolved_at IS NULL THEN 0 ELSE 1 END';

    #[Scope]
    protected function ongoing(Builder $query): void
    {
        $query->whereNull('resolved_at');
    }

    #[Scope]
    protected function sort(Builder $query, ?string $sort, string $direction = 'asc'): void
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $column = self::SORTS[$sort] ?? null;

        match (true) {
            $column !== null => $query->orderBy($column, $direction),

            // A correlated subquery rather than a join, so the paginator's
            // count query stays a plain count over incidents.
            $sort === 'monitor' => $query->orderBy(
                Monitor::query()->select('name')->whereColumn('monitors.id', 'incidents.monitor_id'),
                $direction,
            ),

            $sort === 'status' => $query->orderByRaw(self::RANK.' '.$direction),

            // An open incident has no end, so it is still growing and sorts as
            // the longest. COALESCE to now() rather than leaving it null,
            // which would sort it to one end regardless of how long it has run.
            $sort === 'duration' => $query->orderByRaw(
                SqlDialect::openEndedSeconds('started_at', 'resolved_at').' '.$direction,
            ),

            default => $query->orderByRaw(self::RANK),
        };

        $query->orderByDesc('started_at');
    }
}
