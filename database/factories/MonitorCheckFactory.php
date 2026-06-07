<?php

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitorCheck>
 */
class MonitorCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'checked_at' => now(),
            'response_ms' => $this->faker->numberBetween(50, 1200),
            'is_up' => true,
            'error' => null,
            'meta' => [
                'status_code' => 200,
                'checker' => 'http',
            ],
        ];
    }

    public function down(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_up' => false,
            'meta' => [
                'status_code' => 503,
                'checker' => 'http',
            ],
            'error' => 'Service unavailable',
        ]);
    }
}
