<?php

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    private User $user;

    public function forUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'url' => $this->faker->url(),
            'timeout' => $this->faker->numberBetween(1, 10),
            'check_interval' => $this->faker->numberBetween(1, 60),
            'created_by' => $this->user->id,
        ];
    }
}
