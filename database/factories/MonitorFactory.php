<?php

namespace Database\Factories;

use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    public function forUser(User $user): static
    {
        return $this->state(fn () => ['created_by' => $user->id]);
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'url' => $this->faker->url(),
            'type' => MonitorType::Http,
            'config' => [],
            'timeout' => $this->faker->numberBetween(1, 10),
            'interval_seconds' => 300,
            'confirmation_threshold' => 1,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function type(MonitorType $type, array $config = []): static
    {
        return $this->state(fn () => [
            'type' => $type,
            'config' => $config,
            'url' => $type->expectsUrl() ? 'https://example.com' : 'example.com',
        ]);
    }

    public function up(): static
    {
        return $this->state(fn () => ['latest_is_up' => true, 'last_checked_at' => now()]);
    }

    public function down(): static
    {
        return $this->state(fn () => [
            'latest_is_up' => false,
            'last_checked_at' => now(),
            'failure_streak' => 1,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
