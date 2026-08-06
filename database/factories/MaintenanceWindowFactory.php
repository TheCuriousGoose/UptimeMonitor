<?php

namespace Database\Factories;

use App\Enums\MaintenanceRecurrence;
use App\Models\MaintenanceWindow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceWindow>
 */
class MaintenanceWindowFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Deploy window',
            'recurrence' => MaintenanceRecurrence::Once,
            'timezone' => 'UTC',
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ];
    }

    public function recurring(string $cron = '0 2 * * 0', int $minutes = 60): static
    {
        return $this->state(fn () => [
            'recurrence' => MaintenanceRecurrence::Recurring,
            'cron' => $cron,
            'duration_minutes' => $minutes,
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
