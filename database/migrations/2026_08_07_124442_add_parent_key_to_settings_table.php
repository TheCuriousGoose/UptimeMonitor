<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('parent_key')->nullable()->after('group')->index();
            $table->unsignedInteger('sort_order')->default(0)->after('parent_key');
        });

        // Widened from an enum so new types do not need a schema change;
        // SettingType still constrains the values in PHP.
        Schema::table('settings', function (Blueprint $table) {
            $table->string('type')->default('string')->change();
        });

        // Postgres compiles enum() to a CHECK constraint that outlives the
        // type change, so it would keep rejecting any newly added type.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE settings DROP CONSTRAINT IF EXISTS settings_type_check');
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex(['parent_key']);
            $table->dropColumn(['parent_key', 'sort_order']);
            $table->enum('type', ['string', 'boolean', 'integer', 'float', 'json'])
                ->default('string')
                ->change();
        });
    }
};
