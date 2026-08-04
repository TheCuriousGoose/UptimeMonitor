<?php

namespace Database\Seeders;

use App\Enums\MonitorType;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\User;
use Illuminate\Database\Seeder;

class MonitorSeeder extends Seeder
{
    private const MONITORS = 24;

    private const CHECKS_PER_MONITOR = 480;

    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create();

        foreach (range(1, self::MONITORS) as $index) {
            $type = fake()->randomElement(MonitorType::cases());

            $monitor = Monitor::create([
                'name' => ucfirst(fake()->word()).' '.fake()->randomElement(
                    ['API', 'Service', 'Gateway', 'Server', 'Dashboard', 'Platform', 'Worker', 'Queue'],
                ),
                'url' => $type->expectsUrl() ? fake()->url() : fake()->domainName(),
                'type' => $type,
                'config' => $type->defaultConfig(),
                'created_by' => $user->id,
                'timeout' => 10,
                'interval_seconds' => fake()->randomElement([60, 300, 600]),
                'is_active' => $index % 12 !== 0,
            ]);

            $this->seedHistory($monitor);
        }
    }

    /**
     * Generate a believable check history with a couple of outages so the
     * dashboard, charts and incident list all have something to show.
     */
    private function seedHistory(Monitor $monitor): void
    {
        $rows = [];
        $outageStart = fake()->numberBetween(50, self::CHECKS_PER_MONITOR - 60);
        $outageLength = fake()->numberBetween(3, 12);
        $lastIsUp = true;

        for ($i = 0; $i < self::CHECKS_PER_MONITOR; $i++) {
            $inOutage = $i >= $outageStart && $i < $outageStart + $outageLength;
            $isUp = ! $inOutage && fake()->numberBetween(1, 100) > 1;
            $lastIsUp = $isUp;

            $rows[] = [
                'monitor_id' => $monitor->id,
                'checked_at' => now()->subMinutes((self::CHECKS_PER_MONITOR - $i) * 3),
                'response_ms' => $isUp ? fake()->numberBetween(40, 900) : 0,
                'is_up' => $isUp,
                'error' => $isUp ? null : fake()->randomElement([
                    'HTTP 503', 'Connection timed out', 'HTTP 502', 'Name resolution failed',
                ]),
                'meta' => json_encode(['status_code' => $isUp ? 200 : 503, 'checker' => $monitor->type->value]),
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            MonitorCheck::insert($chunk);
        }

        Incident::create([
            'monitor_id' => $monitor->id,
            'started_at' => now()->subMinutes((self::CHECKS_PER_MONITOR - $outageStart) * 3),
            'resolved_at' => now()->subMinutes((self::CHECKS_PER_MONITOR - $outageStart - $outageLength) * 3),
            'cause' => 'HTTP 503',
            'failed_checks' => $outageLength,
        ]);

        $monitor->forceFill([
            'latest_is_up' => $lastIsUp,
            'last_checked_at' => now(),
            'status_changed_at' => now()->subHours(2),
        ])->save();
    }
}
