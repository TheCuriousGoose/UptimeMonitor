<?php

namespace Database\Seeders;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\User;
use Illuminate\Database\Seeder;

class MonitorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amountOfMonitors = 200;
        $amountOfChecksPerMonitor = 500;
        $user = User::find(2);

        for ($i = 0; $i < $amountOfMonitors; $i++) {
            $monitor = Monitor::create([
                'name' => ucfirst(fake()->word()) . ' ' . fake()->randomElement(['API', 'Service', 'Gateway', 'Server', 'Dashboard', 'Platform', 'Worker', 'Queue']),
                'url' => fake()->url(),
                'created_by' => $user->id,
                'timeout' => 5,
                'check_interval' => '* * * * *'
            ]);

            // Create fake monitor checks for each monitor
            for ($j = 0; $j < $amountOfChecksPerMonitor; $j++) {
                $isUp = rand(1, 100) > 5; // 95% uptime
                $checkedAt = now()->subMinutes($amountOfChecksPerMonitor - $j);

                MonitorCheck::create([
                    'monitor_id' => $monitor->id,
                    'checked_at' => $checkedAt,
                    'status_code' => $isUp ? 200 : fake()->randomElement([500, 503]),
                    'response_ms' => $isUp ? fake()->randomElement(array_merge(array_fill(0, 70, fake()->numberBetween(50, 300)), array_fill(0, 30, fake()->numberBetween(300, 2000)))) : 5001,
                    'is_up' => $isUp,
                    'error' => $isUp ? null : fake()->randomElement([
                        "Connection timeout after 5 seconds",
                        "Service unavailable",
                        "Internal server error",
                        "Gateway timeout",
                        "Service temporarily down"
                    ]),
                ]);
            }
        }
    }
}
