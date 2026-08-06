<?php

namespace Database\Factories;

use App\Enums\IncidentUpdateStatus;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentUpdate>
 */
class IncidentUpdateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'incident_id' => Incident::factory(),
            'user_id' => User::factory(),
            'body' => 'We are looking into it.',
            'is_public' => false,
        ];
    }

    public function public(?IncidentUpdateStatus $status = null): static
    {
        return $this->state(fn () => [
            'is_public' => true,
            'status' => $status ?? IncidentUpdateStatus::Investigating,
        ]);
    }
}
