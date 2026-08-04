<?php

namespace Database\Factories;

use App\Enums\ChannelType;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationChannel>
 */
class NotificationChannelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Ops email',
            'type' => ChannelType::Email,
            'config' => ['email' => $this->faker->safeEmail()],
            'is_active' => true,
        ];
    }

    public function webhook(string $url = 'https://hooks.example.com/uptime'): static
    {
        return $this->state(fn () => [
            'name' => 'Ops webhook',
            'type' => ChannelType::Webhook,
            'config' => ['url' => $url],
        ]);
    }

    public function slack(string $url = 'https://hooks.slack.com/services/T000/B000/XXX'): static
    {
        return $this->state(fn () => [
            'name' => 'Slack',
            'type' => ChannelType::Slack,
            'config' => ['url' => $url],
        ]);
    }

    public function discord(string $url = 'https://discord.com/api/webhooks/1/abc'): static
    {
        return $this->state(fn () => [
            'name' => 'Discord',
            'type' => ChannelType::Discord,
            'config' => ['url' => $url],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
