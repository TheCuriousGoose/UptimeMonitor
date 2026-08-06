<?php

namespace Tests\Unit\Support;

use App\Support\SqlDialect;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * These expressions are string-built, so nothing but a test catches a typo in
 * a dialect the local database never exercises.
 */
class SqlDialectTest extends TestCase
{
    public function test_it_builds_interval_arithmetic_per_driver(): void
    {
        $expected = [
            'mysql' => 'DATE_ADD(NOW(), INTERVAL GREATEST(interval_seconds, 30) SECOND)',
            'mariadb' => 'DATE_ADD(NOW(), INTERVAL GREATEST(interval_seconds, 30) SECOND)',
            'sqlite' => "datetime('now', '+' || MAX(interval_seconds, 30) || ' seconds')",
            'pgsql' => "NOW() + (GREATEST(interval_seconds, 30) || ' seconds')::interval",
        ];

        foreach ($expected as $driver => $sql) {
            $this->assertSame($sql, SqlDialect::nowPlusSeconds('interval_seconds', 30, $driver));
        }
    }

    public function test_it_builds_a_unix_timestamp_per_driver(): void
    {
        $expected = [
            'mysql' => 'UNIX_TIMESTAMP(checked_at)',
            'mariadb' => 'UNIX_TIMESTAMP(checked_at)',
            'sqlite' => "strftime('%s', checked_at)",
            'pgsql' => 'EXTRACT(EPOCH FROM checked_at)',
        ];

        foreach ($expected as $driver => $sql) {
            $this->assertSame($sql, SqlDialect::unixTimestamp('checked_at', $driver));
        }
    }

    public function test_it_builds_a_date_truncation_per_driver(): void
    {
        $this->assertSame('DATE(checked_at)', SqlDialect::dateOf('checked_at', 'mariadb'));
        $this->assertSame('date(checked_at)', SqlDialect::dateOf('checked_at', 'sqlite'));
    }

    /**
     * Silently emitting MySQL syntax on an unknown driver would produce wrong
     * numbers rather than an error, which is the worse failure.
     */
    public function test_an_unsupported_driver_throws(): void
    {
        $this->expectException(RuntimeException::class);

        SqlDialect::unixTimestamp('checked_at', 'sqlsrv');
    }
}
