<?php

use Cron\CronExpression;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cron expressions are a poor fit for "how often should we check this?".
     * Replace them with a plain interval in seconds and give monitors room
     * for per-type configuration and flap protection.
     */
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->unsignedInteger('interval_seconds')->default(300)->after('check_interval');
            $table->json('config')->nullable()->after('url');
            $table->unsignedTinyInteger('confirmation_threshold')->default(1)->after('timeout');
            $table->unsignedInteger('failure_streak')->default(0)->after('latest_is_up');
            $table->unsignedInteger('success_streak')->default(0)->after('failure_streak');
            $table->dateTime('last_checked_at')->nullable()->after('next_check_at');
            $table->dateTime('status_changed_at')->nullable()->after('last_checked_at');
        });

        foreach (DB::table('monitors')->select('id', 'check_interval')->cursor() as $monitor) {
            DB::table('monitors')
                ->where('id', $monitor->id)
                ->update(['interval_seconds' => $this->cronToSeconds($monitor->check_interval)]);
        }

        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn('check_interval');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->string('check_interval', 100)->default('*/5 * * * *')->after('timeout');
        });

        foreach (DB::table('monitors')->select('id', 'interval_seconds')->cursor() as $monitor) {
            DB::table('monitors')
                ->where('id', $monitor->id)
                ->update(['check_interval' => $this->secondsToCron((int) $monitor->interval_seconds)]);
        }

        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn([
                'interval_seconds',
                'config',
                'confirmation_threshold',
                'failure_streak',
                'success_streak',
                'last_checked_at',
                'status_changed_at',
            ]);
        });
    }

    /**
     * Derive an interval from a cron expression by measuring the gap between
     * its next two run dates. Falls back to five minutes for anything unparsable.
     */
    private function cronToSeconds(?string $expression): int
    {
        if (! $expression || ! CronExpression::isValidExpression($expression)) {
            return 300;
        }

        $cron = new CronExpression($expression);
        $first = $cron->getNextRunDate('now', 0, true);
        $second = $cron->getNextRunDate($first, 1, false);

        $seconds = $second->getTimestamp() - $first->getTimestamp();

        return max(30, min($seconds, 86400));
    }

    private function secondsToCron(int $seconds): string
    {
        $minutes = max(1, (int) round($seconds / 60));

        return match (true) {
            $minutes === 1 => '* * * * *',
            $minutes < 60 => "*/{$minutes} * * * *",
            default => '0 * * * *',
        };
    }
};
