<?php

use App\Monitoring\TargetIdentity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The registrable domain a monitor points at, denormalised so the abuse
     * budget can be summed in SQL. Derived in Monitor::saving, never submitted.
     */
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->string('target_domain', 255)->nullable()->after('url');
            $table->index(['target_domain', 'is_active']);
        });

        DB::table('monitors')->orderBy('id')->select('id', 'url')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('monitors')
                    ->where('id', $row->id)
                    ->update(['target_domain' => TargetIdentity::fromTarget($row->url)?->domain]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropIndex(['target_domain', 'is_active']);
            $table->dropColumn('target_domain');
        });
    }
};
