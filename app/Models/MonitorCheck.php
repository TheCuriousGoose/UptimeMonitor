<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[WithoutTimestamps]
#[Fillable('monitor_id', 'status_code', 'response_ms', 'is_up', 'error')]
class MonitorCheck extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (self $check) {
            Monitor::where('id', $check->monitor_id)
                ->whereNotExists(function ($q) use ($check) {
                    $q->from('monitor_checks')
                        ->where('monitor_id', $check->monitor_id)
                        ->where('checked_at', '>', $check->checked_at);
                })
                ->update(['latest_is_up' => $check->is_up]);
        });
    }

    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class);
    }
}
