<?php

namespace App\Models;

use App\Enums\ChannelType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'name', 'type', 'config', 'is_active'])]
class NotificationChannel extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'config' => 'array',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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
