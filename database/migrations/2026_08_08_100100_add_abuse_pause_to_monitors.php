<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A target answering 429 or 403 is asking to be left alone. Tracking the
     * streak separately from failure_streak keeps "refused us" distinct from
     * "is down", which is a different thing to tell the owner.
     */
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->unsignedInteger('refusal_streak')->default(0)->after('degraded_streak');
            $table->timestamp('paused_at')->nullable()->after('is_active');
            $table->string('paused_reason', 255)->nullable()->after('paused_at');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn(['refusal_streak', 'paused_at', 'paused_reason']);
        });
    }
};
