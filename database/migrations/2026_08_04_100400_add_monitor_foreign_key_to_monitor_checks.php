<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * monitor_checks referenced monitors without a constraint, so deleting a
     * monitor left its check history behind forever.
     */
    public function up(): void
    {
        DB::table('monitor_checks')
            ->whereNotIn('monitor_id', DB::table('monitors')->select('id'))
            ->delete();

        Schema::table('monitor_checks', function (Blueprint $table) {
            $table->foreign('monitor_id')->references('id')->on('monitors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('monitor_checks', function (Blueprint $table) {
            $table->dropForeign(['monitor_id']);
        });
    }
};
