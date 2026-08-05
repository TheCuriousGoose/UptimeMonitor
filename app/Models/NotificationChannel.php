<?php

namespace App\Models;

use App\Enums\AlertScope;
use App\Enums\ChannelType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'name', 'type', 'config', 'is_active', 'alert_scope', 'templates'])]
class NotificationChannel extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'config' => 'array',
            'is_active' => 'boolean',
            'alert_scope' => AlertScope::class,
            'templates' => 'array',
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
