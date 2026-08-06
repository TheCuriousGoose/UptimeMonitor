<?php

namespace App\Models;

use App\Casts\EncryptedJson;
use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
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
            'is_active' => 'boolean',
            'latest_is_up' => 'boolean',
            'interval_seconds' => 'integer',
            'confirmation_threshold' => 'integer',
            'recovery_threshold' => 'integer',
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
            $this->latest_is_up === null => MonitorStatus::Pending,
            $this->latest_is_up => MonitorStatus::Up,
            default => MonitorStatus::Down,
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

    public function scopeStatus(Builder $query, ?MonitorStatus $status): Builder
    {
        return match ($status) {
            MonitorStatus::Up => $query->where('is_active', true)->where('latest_is_up', true),
            MonitorStatus::Down => $query->where('is_active', true)->where('latest_is_up', false),
            MonitorStatus::Paused => $query->where('is_active', false),
            MonitorStatus::Pending => $query->where('is_active', true)->whereNull('latest_is_up'),
            null => $query,
        };
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('url', 'LIKE', "%{$search}%");
        });
    }
}
