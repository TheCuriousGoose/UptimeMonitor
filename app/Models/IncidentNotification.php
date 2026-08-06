<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What has been said to one channel about one incident. Records what was
 * queued, not delivered — a confirmed ledger would let a permanently broken
 * channel re-page forever.
 */
#[Fillable([
    'incident_id', 'notification_channel_id', 'notify_count',
    'last_notified_at', 'deferred_until',
])]
class IncidentNotification extends Model
{
    protected function casts(): array
    {
        return [
            'notify_count' => 'integer',
            'last_notified_at' => 'immutable_datetime',
            'deferred_until' => 'immutable_datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(NotificationChannel::class, 'notification_channel_id');
    }
}
