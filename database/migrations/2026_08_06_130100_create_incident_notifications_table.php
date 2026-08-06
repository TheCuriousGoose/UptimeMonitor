<?php

use App\Models\Incident;
use App\Models\NotificationChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The delivery ledger the app never had.
     *
     * Reminder cadence is a per-channel setting, so "when did we last tell
     * this channel" has to be keyed the same way — a single last_notified_at
     * on the incident would let one channel's reminder silence another's.
     */
    public function up(): void
    {
        Schema::create('incident_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Incident::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(NotificationChannel::class)->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('notify_count')->default(0);
            $table->dateTime('last_notified_at')->nullable();
            $table->dateTime('deferred_until')->nullable();
            $table->timestamps();

            $table->unique(['incident_id', 'notification_channel_id'], 'incident_channel_unique');
            $table->index('deferred_until');
        });

        $this->backfillOngoingIncidents();
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_notifications');
    }

    /**
     * Record every open incident as already announced.
     *
     * Without this the first sweep after deploy treats every live outage as
     * never-announced and re-pages the entire fleet, and the "was this
     * announced?" gate would suppress the recovery alert for outages that
     * were in fact announced. Bounded to open incidents, so it is cheap.
     */
    private function backfillOngoingIncidents(): void
    {
        $now = now();

        Incident::query()
            ->whereNull('resolved_at')
            ->with('monitor')
            ->cursor()
            ->each(function (Incident $incident) use ($now): void {
                $monitor = $incident->monitor;

                if ($monitor === null) {
                    return;
                }

                $channels = NotificationChannel::query()
                    ->active()
                    ->forMonitor($monitor)
                    ->pluck('id');

                foreach ($channels as $channelId) {
                    DB::table('incident_notifications')->insertOrIgnore([
                        'incident_id' => $incident->id,
                        'notification_channel_id' => $channelId,
                        'notify_count' => 1,
                        'last_notified_at' => $incident->started_at,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }
};
