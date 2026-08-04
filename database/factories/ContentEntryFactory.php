<?php

namespace Database\Factories;

use App\Enums\ContentType;
use App\Models\ContentEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentEntry>
 */
class ContentEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim($this->faker->sentence(4), '.');

        return [
            'type' => ContentType::Post,
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'excerpt' => $this->faker->sentence(12),
            'body' => '## '.$this->faker->sentence(3)."\n\n".$this->faker->paragraph(5),
            'sort_order' => 0,
            'published_at' => now()->subDays($this->faker->numberBetween(1, 90)),
            'author_id' => User::factory(),
        ];
    }

    public function type(ContentType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['published_at' => now()->addWeek()]);
    }
}
