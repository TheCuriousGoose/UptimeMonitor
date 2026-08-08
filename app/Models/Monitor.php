<?php

namespace App\Models;

use App\Casts\EncryptedJson;
use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Models\Concerns\RoutesByUuid;
use App\Models\Concerns\SortsByAllowlist;
use App\Monitoring\TargetIdentity;
use App\Support\SqlDialect;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
    'target_domain', 'refusal_streak', 'paused_at', 'paused_reason',
])]
class Monitor extends Model
{
    use HasFactory, RoutesByUuid, SortsByAllowlist;

    protected function casts(): array
    {
        return [
            'type' => MonitorType::class,
            'config' => EncryptedJson::class,
            'next_check_at' => 'immutable_datetime',
            'last_checked_at' => 'immutable_datetime',
            'status_changed_at' => 'immutable_datetime',
            'maintenance_until' => 'immutable_datetime',
            'paused_at' => 'immutable_datetime',
            'refusal_streak' => 'integer',
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

        static::saving(function (Monitor $monitor): void {
            if ($monitor->isDirty('url')) {
                $monitor->target_domain = TargetIdentity::forMonitor($monitor)?->domain;
            }
        });
    }

    public function targetIdentity(): ?TargetIdentity
    {
        return TargetIdentity::forMonitor($this);
    }

    /**
     * Requests per minute this monitor contributes to its target's budget.
     */
    public function requestsPerMinute(): float
    {
        return 60 / max(1, (int) $this->interval_seconds);
    }

    public function pauseFor(string $reason): void
    {
        $this->forceFill([
            'is_active' => false,
            'paused_at' => now(),
            'paused_reason' => mb_substr($reason, 0, 255),
        ])->save();
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

    public function maintenanceWindows(): BelongsToMany
    {
        return $this->belongsToMany(MaintenanceWindow::class);
    }

    /**
     * The columns a client may order by, keyed by the name the table sends.
     * Null means the ordering is computed — see {@see sortDerived()}.
     */
    public const SORTS = [
        'name' => 'name',
        'type' => 'type',
        'interval' => 'interval_seconds',
        'last_checked' => 'last_checked_at',
        'status' => null, // Ranked, not a column — see RANK.
    ];

    /**
     * Down first, then pending and up, with paused last. Someone opening the
     * list is looking for what is broken.
     */
    private const RANK = 'CASE WHEN is_active = FALSE THEN 2 WHEN latest_is_up = FALSE THEN 0 ELSE 1 END';

    #[Scope]
    protected function forUser(Builder $query, User $user): void
    {
        $query->where('created_by', $user->id);
    }

    #[Scope]
    protected function forDomain(Builder $query, string $domain): void
    {
        $query->where('target_domain', $domain);
    }

    #[Scope]
    protected function due(Builder $query): void
    {
        $query->where('is_active', true)->where('next_check_at', '<=', now());
    }

    /**
     * Active and not inside a maintenance window — the states that partition
     * into up, degraded, down and pending.
     */
    #[Scope]
    protected function awake(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $inner) => $inner
                ->whereNull('maintenance_until')
                ->orWhere('maintenance_until', '<=', now()));
    }

    /**
     * Named whereStatus rather than status because status() is already the
     * instance accessor, and a scope attribute takes the bare method name.
     */
    #[Scope]
    protected function whereStatus(Builder $query, ?MonitorStatus $status): void
    {
        match ($status) {
            // Degraded is carved out of Up so the filters partition rather
            // than overlap — a monitor is in exactly one of them.
            MonitorStatus::Up => $query->awake()->where('latest_is_up', true)->where('is_degraded', false),
            MonitorStatus::Degraded => $query->awake()->where('latest_is_up', true)->where('is_degraded', true),
            MonitorStatus::Down => $query->awake()->where('latest_is_up', false),
            MonitorStatus::Pending => $query->awake()->whereNull('latest_is_up'),
            MonitorStatus::Maintenance => $query->where('is_active', true)
                ->whereNotNull('maintenance_until')
                ->where('maintenance_until', '>', now()),
            MonitorStatus::Paused => $query->where('is_active', false),
            null => $query,
        };
    }

    protected function sortDerived(Builder $query, ?string $sort, string $direction): void
    {
        $sort === 'status'
            ? $query->orderByRaw(self::RANK.' '.$direction)
            : $query->orderByRaw(self::RANK);
    }

    protected function sortTiebreak(Builder $query): void
    {
        $query->orderBy('name');
    }

    #[Scope]
    protected function search(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $like = SqlDialect::like();

        $query->where(fn (Builder $inner) => $inner
            ->where('name', $like, "%{$search}%")
            ->orWhere('url', $like, "%{$search}%"));
    }
}
