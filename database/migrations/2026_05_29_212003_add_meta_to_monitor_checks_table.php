<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monitor_checks', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('error');
            $table->integer('status_code')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('monitor_checks', function (Blueprint $table) {
            $table->dropColumn('meta');
            $table->smallInteger('status_code')->nullable(false)->change();
        });
    }
};
