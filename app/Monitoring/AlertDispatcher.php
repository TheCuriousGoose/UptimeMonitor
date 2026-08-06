<?php

namespace App\Monitoring;

use App\Jobs\SendAlert;
use App\Models\Incident;
use App\Models\IncidentNotification;
use App\Models\Monitor;
use App\Models\NotificationChannel;

class AlertDispatcher
{
    public function dispatch(Monitor $monitor, AlertMessage $message): void
    {
        // Always queried, never read off a preloaded relation: a channel scoped
        // to every monitor is by definition absent from the pivot, so
        // $monitor->notificationChannels is no longer the complete answer.
        $channels = NotificationChannel::query()
            ->active()
            ->forMonitor($monitor)
            ->get();

        foreach ($channels as $channel) {
            $this->send($channel, $message);
        }
    }

    /**
     * Quiet hours are decided here rather than in StatusEvaluator because they
     * belong to the channel, not the monitor: two channels watching the same
     * outage can be asleep at different times.
     */
    public function send(NotificationChannel $channel, AlertMessage $message): void
    {
        $quiet = $channel->isQuiet($message->occurredAt);

        // Nothing to act on at 3am, so resolutions are dropped rather than held.
        if ($quiet && $message->event->isResolution()) {
            return;
        }

        if ($quiet && $message->incident !== null) {
            $this->defer($channel, $message->incident);

            return;
        }

        // A degradation has no incident to defer against.
        if ($quiet) {
            return;
        }

        SendAlert::dispatch($channel, $message);

        if ($message->incident !== null) {
            $this->recordDelivery($channel, $message->incident);
        }
    }

    /**
     * Held, not dropped: sleeping through an outage that fixed itself is the
     * point; sleeping through one that did not is a missed page.
     */
    private function defer(NotificationChannel $channel, Incident $incident): void
    {
        IncidentNotification::query()->updateOrCreate(
            [
                'incident_id' => $incident->id,
                'notification_channel_id' => $channel->id,
            ],
            ['deferred_until' => $channel->quietWindowEndsAt(now())],
        );
    }

    public function recordDelivery(NotificationChannel $channel, Incident $incident): void
    {
        $ledger = IncidentNotification::query()->firstOrNew([
            'incident_id' => $incident->id,
            'notification_channel_id' => $channel->id,
        ]);

        $ledger->notify_count = (int) $ledger->notify_count + 1;
        $ledger->last_notified_at = now();
        $ledger->deferred_until = null;
        $ledger->save();
    }
}
