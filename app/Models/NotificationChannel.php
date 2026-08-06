<?php

namespace App\Models;

use App\Casts\EncryptedJson;
use App\Enums\AlertScope;
use App\Enums\ChannelType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'user_id', 'name', 'type', 'config', 'is_active', 'alert_scope', 'templates',
    'renotify_minutes', 'renotify_limit',
    'quiet_hours_start', 'quiet_hours_end', 'quiet_hours_timezone',
])]
class NotificationChannel extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            // Holds delivery credentials — see App\Casts\EncryptedJson.
            'config' => EncryptedJson::class,
            'is_active' => 'boolean',
            'alert_scope' => AlertScope::class,
            'templates' => 'array',
            'renotify_minutes' => 'integer',
            'renotify_limit' => 'integer',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Channels that should alert for this monitor: the owner's channels that
     * either cover every monitor or name this one explicitly.
     *
     * Scoping to the monitor's owner rather than the acting user is what stops
     * an admin's own channels firing on someone else's monitor.
     */
    public function scopeForMonitor(Builder $query, Monitor $monitor): Builder
    {
        return $query
            ->where('user_id', $monitor->created_by)
            ->where(fn (Builder $scope) => $scope
                ->where('alert_scope', AlertScope::All->value)
                ->orWhereHas('monitors', fn (Builder $attached) => $attached->whereKey($monitor->getKey())));
    }

    /**
     * Evaluated in the channel's own zone. Handles a window that wraps past
     * midnight — "22:00 to 07:00" would otherwise match nothing.
     */
    public function isQuiet(CarbonInterface $at): bool
    {
        if ($this->quiet_hours_start === null || $this->quiet_hours_end === null) {
            return false;
        }

        $local = CarbonImmutable::parse($at)
            ->setTimezone($this->quiet_hours_timezone ?: config('app.timezone'))
            ->format('H:i');

        $start = substr((string) $this->quiet_hours_start, 0, 5);
        $end = substr((string) $this->quiet_hours_end, 0, 5);

        return $start <= $end
            ? $local >= $start && $local < $end
            : $local >= $start || $local < $end;
    }

    public function quietWindowEndsAt(CarbonInterface $at): CarbonImmutable
    {
        $zone = $this->quiet_hours_timezone ?: config('app.timezone');
        $local = CarbonImmutable::parse($at)->setTimezone($zone);

        [$hour, $minute] = array_map('intval', explode(':', substr((string) $this->quiet_hours_end, 0, 5)));

        $end = $local->setTime($hour, $minute);

        return $end->lessThanOrEqualTo($local) ? $end->addDay() : $end;
    }

    /**
     * The destination this channel delivers to — an address for email, an
     * endpoint for webhook-style channels, a credential for the integrations
     * that authenticate instead of using a secret URL.
     */
    public function destination(): string
    {
        return (string) ($this->config[$this->type->destinationKey()] ?? '');
    }

    /**
     * A destination safe to render. Credentials are masked to their last four
     * characters — enough to tell two keys apart, useless if the page leaks.
     */
    public function maskedDestination(): string
    {
        $destination = $this->destination();

        if (! $this->type->destinationIsSecret() || $destination === '') {
            return $destination;
        }

        return str_repeat('•', 8).substr($destination, -4);
    }
}
