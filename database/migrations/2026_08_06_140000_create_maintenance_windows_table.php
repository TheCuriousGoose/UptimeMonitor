<?php

use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_windows', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('recurrence', 20);
            $table->string('timezone', 64)->default('UTC');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('cron', 100)->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'starts_at']);
        });

        Schema::create('maintenance_window_monitor', function (Blueprint $table) {
            $table->foreignIdFor(MaintenanceWindow::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Monitor::class)->constrained()->cascadeOnDelete();

            $table->unique(['maintenance_window_id', 'monitor_id'], 'window_monitor_unique');
        });

        Schema::table('monitors', function (Blueprint $table) {
            $table->dateTime('maintenance_until')->nullable()->index()->after('status_changed_at');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->boolean('is_maintenance')->default(false)->after('failed_checks');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', fn (Blueprint $table) => $table->dropColumn('is_maintenance'));
        Schema::table('monitors', fn (Blueprint $table) => $table->dropColumn('maintenance_until'));
        Schema::dropIfExists('maintenance_window_monitor');
        Schema::dropIfExists('maintenance_windows');
    }
};
