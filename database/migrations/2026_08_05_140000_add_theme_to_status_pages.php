<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One JSON column rather than a column per setting: the theme is read and
     * written as a whole, is never queried against, and will keep growing as
     * people ask for more of their house style.
     *
     * Existing pages keep null and fall back to the app's own look, so nothing
     * that is already published changes appearance on deploy.
     */
    public function up(): void
    {
        Schema::table('status_pages', function (Blueprint $table) {
            $table->json('theme')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('status_pages', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
