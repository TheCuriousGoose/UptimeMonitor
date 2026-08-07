<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('parent_key')->nullable()->after('group')->index();
            $table->unsignedInteger('sort_order')->default(0)->after('parent_key');

            // Widened from an enum so new types do not need a schema change;
            // SettingType still constrains the values in PHP.
            $table->string('type')->default('string')->change();
        });
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
