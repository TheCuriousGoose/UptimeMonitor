<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The mirror of confirmation_threshold. success_streak was already being
     * counted on every check and never read, so a single good answer flipped a
     * flapping monitor back to up — and the next failure paged everyone again.
     *
     * A default of 1 is exactly the old behaviour, so no backfill is needed
     * and no existing monitor changes when this runs.
     */
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->unsignedTinyInteger('recovery_threshold')
                ->default(1)
                ->after('confirmation_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn('recovery_threshold');
        });
    }
};
