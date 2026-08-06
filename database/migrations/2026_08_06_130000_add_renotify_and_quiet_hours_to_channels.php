<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alerts were strictly edge-triggered: one message when a monitor went
     * down and one when it came back. An outage that started at 2am and was
     * still running at 9am had said nothing since 2am.
     *
     * All-null is off, which is exactly today's behaviour for every existing
     * channel. No backfill.
     */
    public function up(): void
    {
        Schema::table('notification_channels', function (Blueprint $table) {
            $table->unsignedSmallInteger('renotify_minutes')->nullable()->after('alert_scope');
            $table->unsignedTinyInteger('renotify_limit')->default(3)->after('renotify_minutes');
            $table->time('quiet_hours_start')->nullable()->after('renotify_limit');
            $table->time('quiet_hours_end')->nullable()->after('quiet_hours_start');
            $table->string('quiet_hours_timezone', 64)->nullable()->after('quiet_hours_end');
        });
    }

    public function down(): void
    {
        Schema::table('notification_channels', function (Blueprint $table) {
            $table->dropColumn([
                'renotify_minutes',
                'renotify_limit',
                'quiet_hours_start',
                'quiet_hours_end',
                'quiet_hours_timezone',
            ]);
        });
    }
};
