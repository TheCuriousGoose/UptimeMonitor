<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'started_at' => now()->subHour(),
            'resolved_at' => null,
            'cause' => 'HTTP 503',
            'failed_checks' => 1,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => ['resolved_at' => now()->subMinutes(30)]);
    }
}
