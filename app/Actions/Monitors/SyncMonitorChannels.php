<?php

namespace App\Actions\Monitors;

use App\Models\Monitor;
use App\Models\NotificationChannel;

/**
 * Attach notification channels to a monitor by uuid.
 *
 * Channels are resolved against the monitor's owner, never the acting user,
 * so an admin editing someone else's monitor cannot attach their own
 * endpoints. The web and API controllers held byte-identical copies of this,
 * with a comment on the API one promising it "mirrors the web controller
 * exactly" — a rule that is better enforced than documented.
 */
class SyncMonitorChannels
{
    /**
     * @param  array<int, string>  $uuids
     */
    public function __invoke(Monitor $monitor, array $uuids): void
    {
        $ids = NotificationChannel::query()
            ->where('user_id', $monitor->created_by)
            ->whereIn('uuid', $uuids)
            ->pluck('id');

        $monitor->notificationChannels()->sync($ids);
    }
}
