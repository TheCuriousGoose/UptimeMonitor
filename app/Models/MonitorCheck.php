<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[WithoutTimestamps]
#[Fillable(['monitor_id', 'status_code', 'response_ms', 'is_up', 'error', 'checked_at', 'meta'])]
class MonitorCheck extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'is_up' => 'boolean',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
