<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[WithoutTimestamps]
#[Fillable('monitor_id', 'status_code', 'response_ms', 'is_up', 'error')]
class MonitorCheck extends Model
{
    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class);
    }
}
