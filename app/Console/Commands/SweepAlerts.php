<?php

namespace App\Console\Commands;

use App\Enums\MaintenanceRecurrence;
use App\Models\Incident;
use App\Models\IncidentNotification;
use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Monitoring\AlertDispatcher;
use App\Monitoring\AlertMessage;
use App\Monitoring\MaintenanceSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * The time-driven half of alerting.
 *
 * A sweep rather than delayed jobs, because a delayed reminder would need
 * cancelling whenever the incident resolved and there is no reliable
 * cancellation. Separate from DispatchDueChecks so a slow notifier cannot
 * delay the next round of checks.
 */
class SweepAlerts extends Command
{
    protected $signature = 'monitors:sweep-alerts';

    protected $description = 'Deliver deferred alerts and remind about ongoing incidents';

    public function __construct(private readonly MaintenanceSchedule $maintenance)
    {
        parent::__construct();
    }

    public function handle(AlertDispatcher $dispatcher): int
    {
        $lock = Cache::lock('monitors:sweep-alerts', 55);

        if (! $lock->get()) {
            $this->warn('Another sweep is already running. Skipping.');

            return self::SUCCESS;
        }

        try {
            $this->refreshMaintenance();
            $released = $this->releaseMaintained($dispatcher);
            $delivered = $this->deliverDeferred($dispatcher);
            $reminded = $this->remind($dispatcher);
        } finally {
            $lock->release();
        }

        $this->info(
            "Released {$released} outage(s), delivered {$delivered} deferred alert(s), "
            ."sent {$reminded} reminder(s).",
        );

        return self::SUCCESS;
    }

    /**
     * Recompute monitors.maintenance_until, the cache the badge and the
     * status filters read so they need no per-row schedule lookup.
     */
    private function refreshMaintenance(): void
    {
        $now = now();

        MaintenanceWindow::query()->active()->with('monitors')->cursor()
            ->each(function (MaintenanceWindow $window) use ($now): void {
                if (! $window->coversAt($now)) {
                    return;
                }

                $until = $window->recurrence === MaintenanceRecurrence::Once
                    ? $window->ends_at
                    : $now->copy()->addMinutes((int) $window->duration_minutes);

                Monitor::query()
                    ->whereKey($window->monitors->modelKeys())
                    ->where(fn ($q) => $q->whereNull('maintenance_until')
                        ->orWhere('maintenance_until', '<', $until))
                    ->update(['maintenance_until' => $until]);
            });
    }

    /**
     * An outage that outlived its window is a real one. This is why the
     * incident is flagged rather than not created.
     */
    private function releaseMaintained(AlertDispatcher $dispatcher): int
    {
        $released = 0;

        Incident::query()
            ->ongoing()
            ->where('is_maintenance', true)
            ->with('monitor')
            ->cursor()
            ->each(function (Incident $incident) use ($dispatcher, &$released): void {
                $monitor = $incident->monitor;

                if ($monitor === null || $this->maintenance->covers($monitor, now())) {
                    return;
                }

                $incident->update(['is_maintenance' => false]);

                $dispatcher->dispatch($monitor, AlertMessage::down(
                    $monitor,
                    $incident->cause,
                    $incident,
                ));

                $released++;
            });

        return $released;
    }

    private function deliverDeferred(AlertDispatcher $dispatcher): int
    {
        $sent = 0;

        IncidentNotification::query()
            ->whereNotNull('deferred_until')
            ->where('deferred_until', '<=', now())
            ->with(['incident.monitor', 'channel'])
            ->cursor()
            ->each(function (IncidentNotification $ledger) use ($dispatcher, &$sent): void {
                $incident = $ledger->incident;
                $channel = $ledger->channel;

                // Resolved while we were asleep — nothing to act on.
                if ($incident === null || $channel === null || ! $incident->isOngoing()) {
                    $ledger->update(['deferred_until' => null]);

                    return;
                }

                $dispatcher->send($channel, AlertMessage::down(
                    $incident->monitor,
                    $incident->cause,
                    $incident,
                ));

                $sent++;
            });

        return $sent;
    }

    private function remind(AlertDispatcher $dispatcher): int
    {
        $sent = 0;

        Incident::query()
            ->ongoing()
            ->with('monitor')
            ->cursor()
            ->each(function (Incident $incident) use ($dispatcher, &$sent): void {
                $monitor = $incident->monitor;

                if ($monitor === null) {
                    return;
                }

                $channels = NotificationChannel::query()
                    ->active()
                    ->forMonitor($monitor)
                    ->whereNotNull('renotify_minutes')
                    ->get();

                foreach ($channels as $channel) {
                    if ($this->shouldRemind($incident, $channel)) {
                        $dispatcher->send($channel, AlertMessage::reminder($monitor, $incident));
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    private function shouldRemind(Incident $incident, NotificationChannel $channel): bool
    {
        $ledger = IncidentNotification::query()
            ->where('incident_id', $incident->id)
            ->where('notification_channel_id', $channel->id)
            ->first();

        // A suppressed outage must not start reminding about itself.
        if ($ledger === null || $ledger->notify_count < 1) {
            return false;
        }

        if ($ledger->notify_count > (int) $channel->renotify_limit) {
            return false;
        }

        return $ledger->last_notified_at === null
            || $ledger->last_notified_at->addMinutes((int) $channel->renotify_minutes)->isPast();
    }
}
