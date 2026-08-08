<?php

namespace App\Models;

use App\Enums\MaintenanceRecurrence;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'user_id', 'name', 'recurrence', 'timezone', 'starts_at', 'ends_at',
    'cron', 'duration_minutes', 'is_active',
])]
class MaintenanceWindow extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'recurrence' => MaintenanceRecurrence::class,
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function coversAt(CarbonInterface $moment): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($this->recurrence) {
            MaintenanceRecurrence::Once => $this->starts_at !== null
                && $this->ends_at !== null
                && $moment->greaterThanOrEqualTo($this->starts_at)
                && $moment->lessThan($this->ends_at),

            MaintenanceRecurrence::Recurring => $this->recurringCovers($moment),
        };
    }

    public function nextOccurrenceAt(?CarbonInterface $after = null): ?CarbonImmutable
    {
        if ($this->recurrence === MaintenanceRecurrence::Once) {
            return $this->starts_at !== null
                ? CarbonImmutable::parse($this->starts_at)
                : null;
        }

        if (! $this->cron || ! CronExpression::isValidExpression($this->cron)) {
            return null;
        }

        $local = CarbonImmutable::parse($after ?? now())->setTimezone($this->zone());

        return CarbonImmutable::parse(
            (new CronExpression($this->cron))->getNextRunDate($local),
            $this->zone(),
        )->utc();
    }

    /**
     * Cron is evaluated in the window's own zone: "every Sunday at 02:00" is a
     * wall-clock statement and has to survive a DST shift.
     */
    private function recurringCovers(CarbonInterface $moment): bool
    {
        if (! $this->cron || ! $this->duration_minutes || ! CronExpression::isValidExpression($this->cron)) {
            return false;
        }

        $local = CarbonImmutable::parse($moment)->setTimezone($this->zone());

        $previous = CarbonImmutable::parse(
            (new CronExpression($this->cron))->getPreviousRunDate($local, 0, true),
            $this->zone(),
        );

        return $local->lessThan($previous->addMinutes($this->duration_minutes));
    }

    private function zone(): string
    {
        return $this->timezone ?: config('app.timezone');
    }
}
