<?php

use App\Models\Monitor;
use Carbon\Carbon;
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
            $table->string('type', 50)->default('http')->after('uuid');
            $table->dateTime('next_check_at')->nullable()->after('check_interval');
            $table->index('next_check_at');
        });

        Monitor::query()
            ->whereNull('next_check_at')
            ->where('is_active', true)
            ->update([
                'next_check_at' => Carbon::now()
            ]);
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropIndex(['next_check_at']);
            $table->dropColumn(['type', 'next_check_at']);
        });
    }
};
