<?php

namespace App\Models;

use App\Casts\EncryptedJson;
use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Support\SqlDialect;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'url', 'created_by', 'timeout', 'interval_seconds', 'type', 'config',
    'confirmation_threshold', 'recovery_threshold', 'next_check_at', 'latest_is_up',
    'is_active', 'failure_streak', 'success_streak', 'last_checked_at', 'status_changed_at',
    'degraded_response_ms', 'is_degraded', 'degraded_streak', 'maintenance_until',
])]
class Monitor extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'type' => MonitorType::class,
            'config' => EncryptedJson::class,
            'next_check_at' => 'immutable_datetime',
            'last_checked_at' => 'immutable_datetime',
            'status_changed_at' => 'immutable_datetime',
            'maintenance_until' => 'immutable_datetime',
            'is_active' => 'boolean',
            'latest_is_up' => 'boolean',
            'interval_seconds' => 'integer',
            'confirmation_threshold' => 'integer',
            'recovery_threshold' => 'integer',
            'degraded_response_ms' => 'integer',
            'is_degraded' => 'boolean',
            'degraded_streak' => 'integer',
            'failure_streak' => 'integer',
            'success_streak' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Monitor $monitor): void {
            $monitor->next_check_at ??= now();
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

    public function checks(): HasMany
    {
        return $this->hasMany(MonitorCheck::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notificationChannels(): BelongsToMany
    {
        return $this->belongsToMany(NotificationChannel::class);
    }

    public function statusPages(): BelongsToMany
    {
        return $this->belongsToMany(StatusPage::class);
    }

    /**
     * The open incident for this monitor, if it is currently down.
     */
    public function ongoingIncident(): ?Incident
    {
        return $this->incidents()->whereNull('resolved_at')->latest('started_at')->first();
    }

    /**
     * Stored configuration merged over the defaults for this monitor type.
     *
     * @return array<string, mixed>
     */
    public function resolvedConfig(): array
    {
        return array_merge($this->type->defaultConfig(), $this->config ?? []);
    }

    public function status(): MonitorStatus
    {
        return match (true) {
            ! $this->is_active => MonitorStatus::Paused,
            // A cache refreshed by the sweep, never the source of truth for
            // suppression — StatusEvaluator always asks the schedule live.
            $this->maintenance_until !== null
                && $this->maintenance_until->isFuture() => MonitorStatus::Maintenance,
            $this->latest_is_up === null => MonitorStatus::Pending,
            $this->latest_is_up === false => MonitorStatus::Down,
            (bool) $this->is_degraded => MonitorStatus::Degraded,
            default => MonitorStatus::Up,
        };
    }

    public function nextCheckFrom(?CarbonInterface $from = null): CarbonInterface
    {
        return ($from ?? now())->addSeconds(max(30, $this->interval_seconds));
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('created_by', $user->id);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('next_check_at', '<=', now());
    }

    public function maintenanceWindows(): BelongsToMany
    {
        return $this->belongsToMany(MaintenanceWindow::class);
    }

    public function scopeStatus(Builder $query, ?MonitorStatus $status): Builder
    {
        $awake = fn (Builder $q) => $q->where('is_active', true)
            ->where(fn (Builder $inner) => $inner
                ->whereNull('maintenance_until')
                ->orWhere('maintenance_until', '<=', now()));

        return match ($status) {
            // Degraded is carved out of Up so the filters partition rather
            // than overlap — a monitor is in exactly one of them.
            MonitorStatus::Up => $awake($query)->where('latest_is_up', true)->where('is_degraded', false),
            MonitorStatus::Degraded => $awake($query)->where('latest_is_up', true)->where('is_degraded', true),
            MonitorStatus::Down => $awake($query)->where('latest_is_up', false),
            MonitorStatus::Maintenance => $query->where('is_active', true)
                ->whereNotNull('maintenance_until')
                ->where('maintenance_until', '>', now()),
            MonitorStatus::Paused => $query->where('is_active', false),
            MonitorStatus::Pending => $awake($query)->whereNull('latest_is_up'),
            null => $query,
        };
    }

    /**
     * The columns a client may order by, keyed by the name the table sends.
     *
     * An allowlist rather than a passthrough: `direction` is validated, but
     * the column would otherwise be interpolated straight into orderBy.
     */
    public const SORTS = [
        'name' => 'name',
        'type' => 'type',
        'interval' => 'interval_seconds',
        'last_checked' => 'last_checked_at',
        'status' => null, // Ranked, not a column — see scopeSort().
    ];

    public function scopeSort(Builder $query, ?string $sort, string $direction = 'asc'): Builder
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($sort === null || ! array_key_exists($sort, self::SORTS)) {
            // Down first, then pending and up, with paused last. Someone
            // opening this page is looking for what is broken.
            return $query
                ->orderByRaw('CASE WHEN is_active = FALSE THEN 2 WHEN latest_is_up = FALSE THEN 0 ELSE 1 END')
                ->orderBy('name');
        }

        if ($sort === 'status') {
            return $query
                ->orderByRaw(
                    'CASE WHEN is_active = FALSE THEN 2 WHEN latest_is_up = FALSE THEN 0 ELSE 1 END '.$direction,
                )
                ->orderBy('name');
        }

        return $query->orderBy(self::SORTS[$sort], $direction)->orderBy('name');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        $like = SqlDialect::like();

        return $query->where(function (Builder $q) use ($search, $like) {
            $q->where('name', $like, "%{$search}%")
                ->orWhere('url', $like, "%{$search}%");
        });
    }
}
