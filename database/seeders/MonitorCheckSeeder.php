<?php

namespace Database\Seeders;

use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\User;
use Cron\CronExpression;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MonitorCheckSeeder extends Seeder
{
    private const HISTORY_MONTHS = 2;

    private const INSERT_CHUNK_SIZE = 2000;

    public function run(): void
    {
        $endAt = Carbon::now()->setSecond(0);
        $defaultStartAt = $endAt->copy()->subMonths(self::HISTORY_MONTHS);

        $monitors = Monitor::query()
            ->where('type', MonitorType::Http->value)
            ->get();

        if ($monitors->isEmpty()) {
            $monitors = $this->createHttpMonitors();
        }

        foreach ($monitors as $monitor) {
            $latestCheckAt = $monitor->checks()->max('checked_at');
            $from = $latestCheckAt ? Carbon::parse($latestCheckAt) : $defaultStartAt;

            if ($from->gte($endAt)) {
                continue;
            }

            $cron = new CronExpression($monitor->check_interval);
            $lastIsUp = true;

            $buffer = [];

            $current = Carbon::instance($cron->getNextRunDate($from->toDateTimeString(), 0, false));

            while ($current->lte($endAt)) {
                $payload = $this->fakeHttpCheck($monitor->timeout, $current);
                $lastIsUp = $payload['is_up'];

                $buffer[] = array_merge($payload, [
                    'meta' => json_encode($payload['meta']),
                    'monitor_id' => $monitor->id,
                ]);

                if (count($buffer) >= self::INSERT_CHUNK_SIZE) {
                    MonitorCheck::query()->insert($buffer);
                    $buffer = [];
                }

                $current = Carbon::instance($cron->getNextRunDate($current->toDateTimeString()));
            }

            if ($buffer !== []) {
                MonitorCheck::query()->insert($buffer);
            }

            $monitor->forceFill([
                'latest_is_up' => $lastIsUp,
                'next_check_at' => Carbon::instance($cron->getNextRunDate($endAt->toDateTimeString())),
            ])->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function fakeHttpCheck(int $timeoutSeconds, Carbon $checkedAt): array
    {
        $isUp = fake()->boolean(98);
        $statusCode = $isUp
            ? fake()->randomElement([200, 200, 200, 200, 204, 206])
            : fake()->randomElement([500, 502, 503, 504, 522]);

        return [
            'checked_at' => $checkedAt,
            'response_ms' => $isUp
                ? fake()->numberBetween(45, 900)
                : fake()->numberBetween(max(1000, $timeoutSeconds * 1000), max(2500, ($timeoutSeconds + 3) * 1000)),
            'is_up' => $isUp,
            'error' => $isUp ? null : fake()->randomElement([
                'Connection timed out',
                'TLS handshake failed',
                'Upstream returned an invalid response',
                'Remote host closed the connection',
                'Service unavailable',
            ]),
            'meta' => [
                'status_code' => $statusCode,
                'checker' => MonitorType::Http->value,
            ],
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Monitor>
     */
    private function createHttpMonitors()
    {
        $user = User::query()->first() ?? User::factory()->create();
        $monitors = collect();

        for ($index = 0; $index < 12; $index++) {
            $monitors->push(Monitor::query()->create([
                'name' => ucfirst(fake()->words(2, true)).' '.fake()->randomElement(['API', 'Gateway', 'Website', 'Service']),
                'url' => 'https://'.fake()->domainName().'/'.Str::slug(fake()->words(2, true)),
                'created_by' => $user->id,
                'timeout' => fake()->numberBetween(3, 10),
                'check_interval' => '*/5 * * * *',
                'type' => MonitorType::Http,
                'next_check_at' => now()->addMinutes(5),
                'latest_is_up' => true,
                'is_active' => true,
            ]));
        }

        return $monitors;
    }
}