<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Up but slow" had nowhere to live: response_ms was recorded and charted
     * but never compared to anything, so a service crawling at ten seconds a
     * request looked identical to a healthy one.
     *
     * The threshold is a column beside timeout and confirmation_threshold
     * rather than a key in config, because every checker reports a duration —
     * it is not HTTP-specific. Putting it in config would also mean changing a
     * monitor's type silently discarded it, since MonitorRequest intersects
     * config against the new type's keys.
     *
     * A null threshold means the feature is off, which is what every existing
     * monitor gets. No backfill.
     */
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->unsignedInteger('degraded_response_ms')->nullable()->after('timeout');
            $table->boolean('is_degraded')->default(false)->after('latest_is_up');
            $table->unsignedInteger('degraded_streak')->default(0)->after('success_streak');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn(['degraded_response_ms', 'is_degraded', 'degraded_streak']);
        });
    }
};
