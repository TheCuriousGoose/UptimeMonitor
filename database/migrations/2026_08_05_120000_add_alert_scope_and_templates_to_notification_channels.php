<?php

use App\Enums\AlertScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_channels', function (Blueprint $table) {
            $table->string('alert_scope', 20)->default(AlertScope::All->value)->after('is_active');
            $table->json('templates')->nullable()->after('alert_scope');
        });

        // Every channel that already exists alerts strictly on the monitors in
        // its pivot. Defaulting those to 'all' would silently widen live
        // alerting on deploy, so they are pinned to 'selected' instead — only
        // channels created from here on inherit the 'all' default.
        DB::table('notification_channels')->update(['alert_scope' => AlertScope::Selected->value]);
    }

    public function down(): void
    {
        Schema::table('notification_channels', function (Blueprint $table) {
            $table->dropColumn(['alert_scope', 'templates']);
        });
    }
};
