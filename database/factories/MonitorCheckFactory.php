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
            'status_code' => 200,
            'response_ms' => $this->faker->numberBetween(50, 1200),
            'is_up' => true,
            'error' => null,
        ];
    }

    public function down(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_up' => false,
            'status_code' => 503,
            'error' => 'Service unavailable',
        ]);
    }
}
