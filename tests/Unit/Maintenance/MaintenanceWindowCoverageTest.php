<?php

namespace Tests\Unit\Maintenance;

use App\Enums\MaintenanceRecurrence;
use App\Models\MaintenanceWindow;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class MaintenanceWindowCoverageTest extends TestCase
{
    private function window(array $attributes): MaintenanceWindow
    {
        return new MaintenanceWindow(array_merge([
            'name' => 'Window',
            'timezone' => 'UTC',
            'is_active' => true,
        ], $attributes));
    }

    public function test_a_one_off_window_covers_its_own_interval(): void
    {
        $window = $this->window([
            'recurrence' => MaintenanceRecurrence::Once,
            'starts_at' => CarbonImmutable::parse('2026-03-01 10:00'),
            'ends_at' => CarbonImmutable::parse('2026-03-01 11:00'),
        ]);

        $this->assertFalse($window->coversAt(CarbonImmutable::parse('2026-03-01 09:59')));
        $this->assertTrue($window->coversAt(CarbonImmutable::parse('2026-03-01 10:00')));
        $this->assertTrue($window->coversAt(CarbonImmutable::parse('2026-03-01 10:59')));
        // Exclusive end, so back-to-back windows do not overlap.
        $this->assertFalse($window->coversAt(CarbonImmutable::parse('2026-03-01 11:00')));
    }

    public function test_a_recurring_window_covers_only_its_duration(): void
    {
        $window = $this->window([
            'recurrence' => MaintenanceRecurrence::Recurring,
            'cron' => '0 2 * * 0',
            'duration_minutes' => 60,
        ]);

        // 2026-03-01 is a Sunday.
        $this->assertTrue($window->coversAt(CarbonImmutable::parse('2026-03-01 02:30')));
        $this->assertFalse($window->coversAt(CarbonImmutable::parse('2026-03-01 03:30')));
        $this->assertFalse($window->coversAt(CarbonImmutable::parse('2026-03-02 02:30')));
    }

    /**
     * "Every Sunday at 02:00" is a wall-clock statement, so it has to stay at
     * 02:00 local across a DST shift rather than drifting an hour in UTC.
     */
    public function test_a_recurring_window_survives_a_dst_shift(): void
    {
        $window = $this->window([
            'recurrence' => MaintenanceRecurrence::Recurring,
            'timezone' => 'Europe/Amsterdam',
            'cron' => '0 2 * * 0',
            'duration_minutes' => 60,
        ]);

        // Amsterdam is UTC+1 in winter: 02:30 local is 01:30 UTC.
        $this->assertTrue($window->coversAt(CarbonImmutable::parse('2026-02-01 01:30', 'UTC')));

        // And UTC+2 in summer: 02:30 local is 00:30 UTC.
        $this->assertTrue($window->coversAt(CarbonImmutable::parse('2026-07-05 00:30', 'UTC')));
        $this->assertFalse($window->coversAt(CarbonImmutable::parse('2026-07-05 01:30', 'UTC')));
    }

    public function test_an_inactive_window_never_covers(): void
    {
        $window = $this->window([
            'recurrence' => MaintenanceRecurrence::Once,
            'starts_at' => CarbonImmutable::parse('2026-03-01 10:00'),
            'ends_at' => CarbonImmutable::parse('2026-03-01 11:00'),
            'is_active' => false,
        ]);

        $this->assertFalse($window->coversAt(CarbonImmutable::parse('2026-03-01 10:30')));
    }

    public function test_an_incomplete_window_never_covers(): void
    {
        $recurring = $this->window([
            'recurrence' => MaintenanceRecurrence::Recurring,
            'cron' => null,
            'duration_minutes' => 60,
        ]);

        $this->assertFalse($recurring->coversAt(CarbonImmutable::now()));
    }
}
