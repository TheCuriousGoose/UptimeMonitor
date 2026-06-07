<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->boolean('latest_is_up')->nullable()->after('check_interval');
            $table->index(['created_by', 'latest_is_up']);
        });

        Schema::table('monitor_checks', function (Blueprint $table) {
            $table->index(['monitor_id', 'checked_at']);
        });

        DB::statement('
            UPDATE monitors
            SET latest_is_up = (
                SELECT is_up
                FROM monitor_checks
                WHERE monitor_id = monitors.id
                ORDER BY checked_at DESC
                LIMIT 1
            )
        ');
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropIndex(['created_by', 'latest_is_up']);
            $table->dropColumn('latest_is_up');
        });

        Schema::table('monitor_checks', function (Blueprint $table) {
            $table->dropIndex(['monitor_id', 'checked_at']);
        });
    }
};
