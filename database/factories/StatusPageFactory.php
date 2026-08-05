<?php

namespace Database\Factories;

use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StatusPage>
 */
class StatusPageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'slug' => Str::slug($this->faker->unique()->words(2, true)).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'title' => 'Acme Status',
            'description' => 'Live availability for our services.',
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    /**
     * A page carrying a house style, as opposed to one left on the defaults.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function themed(array $overrides = []): static
    {
        return $this->state(fn () => ['theme' => array_merge([
            'mode' => 'dark',
            'brand_color' => '#ff6600',
            'background' => '#101820',
            'font_family' => "'Acme Grotesk', sans-serif",
            'radius' => 12,
            'width' => 1024,
            'logo_url' => 'https://acme.test/logo.svg',
            'footer_text' => '© Acme B.V.',
            'links' => [['label' => 'Website', 'url' => 'https://acme.test']],
        ], $overrides)]);
    }
}
