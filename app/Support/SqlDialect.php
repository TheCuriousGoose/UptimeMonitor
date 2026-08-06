<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The handful of expressions that cannot be written portably.
 *
 * Scheduling and the uptime rollups both need date arithmetic that has no
 * common spelling, and rewriting them in the query builder is not an option:
 * the batch claim in DispatchDueChecks has to stay a single statement or it
 * deadlocks against StatusEvaluator. Keeping the dialects together means
 * adding a driver is one file rather than a hunt through the codebase.
 *
 * Unsupported drivers throw rather than falling back to MySQL syntax. These
 * queries are already MySQL-only today, so a loud failure is strictly better
 * than silently producing wrong numbers.
 */
final class SqlDialect
{
    /**
     * `now() + max($column, $minimum)` seconds, for claiming a batch of due
     * monitors in one UPDATE.
     */
    public static function nowPlusSeconds(string $column, int $minimum, ?string $driver = null): string
    {
        return match ($driver ??= self::driver()) {
            'mysql', 'mariadb' => "DATE_ADD(NOW(), INTERVAL GREATEST({$column}, {$minimum}) SECOND)",
            'sqlite' => "datetime('now', '+' || MAX({$column}, {$minimum}) || ' seconds')",
            'pgsql' => "NOW() + (GREATEST({$column}, {$minimum}) || ' seconds')::interval",
            default => self::unsupported($driver),
        };
    }

    /**
     * The column as a Unix timestamp, for bucketing a series.
     */
    public static function unixTimestamp(string $column, ?string $driver = null): string
    {
        return match ($driver ??= self::driver()) {
            'mysql', 'mariadb' => "UNIX_TIMESTAMP({$column})",
            'sqlite' => "strftime('%s', {$column})",
            'pgsql' => "EXTRACT(EPOCH FROM {$column})",
            default => self::unsupported($driver),
        };
    }

    /**
     * The date part of a timestamp column, for daily rollups.
     */
    public static function dateOf(string $column, ?string $driver = null): string
    {
        return match ($driver ??= self::driver()) {
            'mysql', 'mariadb', 'pgsql' => "DATE({$column})",
            'sqlite' => "date({$column})",
            default => self::unsupported($driver),
        };
    }

    /**
     * Elapsed seconds between two timestamp columns, treating a null end as
     * "still running" — an open incident is still growing, so it must sort as
     * the longest rather than to whichever end nulls happen to land on.
     */
    public static function openEndedSeconds(string $start, string $end, ?string $driver = null): string
    {
        return match ($driver ??= self::driver()) {
            'mysql', 'mariadb' => "TIMESTAMPDIFF(SECOND, {$start}, COALESCE({$end}, NOW()))",
            'sqlite' => "(strftime('%s', COALESCE({$end}, 'now')) - strftime('%s', {$start}))",
            'pgsql' => "EXTRACT(EPOCH FROM (COALESCE({$end}, NOW()) - {$start}))",
            default => self::unsupported($driver),
        };
    }

    private static function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    private static function unsupported(string $driver): never
    {
        throw new RuntimeException(
            "Unsupported database driver for monitoring queries: {$driver}",
        );
    }
}
