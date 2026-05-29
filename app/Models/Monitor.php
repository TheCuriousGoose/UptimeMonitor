<?php

namespace App\Models;

use App\Enums\MonitorType;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'url', 'created_by', 'timeout', 'check_interval', 'type', 'next_check_at', 'is_active'])]
class Monitor extends Model
{
    use HasUuids, HasFactory;

    protected function casts(): array
    {
        return [
            'type'          => MonitorType::class,
            'next_check_at' => 'immutable_datetime',
            'is_active'     => 'boolean',
            'latest_is_up'  => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Monitor $monitor): void {
            if (! $monitor->next_check_at && $monitor->check_interval) {
                $monitor->next_check_at = (new CronExpression($monitor->check_interval))
                    ->getNextRunDate();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function monitorChecks(): HasMany
    {
        return $this->hasMany(MonitorCheck::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isUp(): bool
    {
        return (bool) $this->monitorChecks()->latest('checked_at')->value('is_up');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('created_by', $user->id);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) return $query;

        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('url', 'LIKE', "%{$search}%");
        });
    }

}
