<?php

namespace Tests\Feature\Monitors;

use App\Enums\MonitorType;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function user(): User
    {
        return User::factory()->withRole('User')->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Marketing site',
            'url' => 'https://example.com',
            'type' => MonitorType::Http->value,
            'timeout' => 10,
            'interval_seconds' => 300,
            'is_active' => true,
        ], $overrides);
    }

    public function test_a_user_can_create_a_monitor(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->post(route('monitors.store'), $this->payload());

        $monitor = Monitor::first();

        $response->assertRedirect(route('monitors.show', $monitor));
        $this->assertSame('Marketing site', $monitor->name);
        $this->assertSame(300, $monitor->interval_seconds);
        $this->assertSame($user->id, $monitor->created_by);
        $this->assertNotNull($monitor->next_check_at, 'A new monitor should be due immediately.');
    }

    public function test_creating_a_keyword_monitor_stores_its_config(): void
    {
        $response = $this->actingAs($this->user())->post(route('monitors.store'), $this->payload([
            'type' => MonitorType::Keyword->value,
            'config' => ['keyword' => 'operational', 'invert' => false],
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame('operational', Monitor::first()->config['keyword']);
    }

    public function test_a_keyword_monitor_requires_a_keyword(): void
    {
        $response = $this->actingAs($this->user())->post(route('monitors.store'), $this->payload([
            'type' => MonitorType::Keyword->value,
        ]));

        $response->assertSessionHasErrors('config.keyword');
        $this->assertSame(0, Monitor::count());
    }

    public function test_a_port_monitor_accepts_a_bare_hostname(): void
    {
        $response = $this->actingAs($this->user())->post(route('monitors.store'), $this->payload([
            'type' => MonitorType::Port->value,
            'url' => 'db.example.com',
            'config' => ['port' => 5432],
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame(5432, Monitor::first()->config['port']);
    }

    public function test_an_http_monitor_rejects_a_bare_hostname(): void
    {
        $response = $this->actingAs($this->user())->post(route('monitors.store'), $this->payload([
            'url' => 'example.com',
        ]));

        $response->assertSessionHasErrors('url');
    }

    public function test_a_port_monitor_rejects_a_url(): void
    {
        $response = $this->actingAs($this->user())->post(route('monitors.store'), $this->payload([
            'type' => MonitorType::Port->value,
            'url' => 'https://db.example.com',
            'config' => ['port' => 5432],
        ]));

        $response->assertSessionHasErrors('url');
    }

    public function test_config_keys_from_another_type_are_discarded(): void
    {
        $this->actingAs($this->user())->post(route('monitors.store'), $this->payload([
            'type' => MonitorType::Http->value,
            'config' => ['keyword' => 'sneaky', 'method' => 'HEAD'],
        ]));

        $config = Monitor::first()->config;

        $this->assertArrayNotHasKey('keyword', $config);
        $this->assertSame('HEAD', $config['method']);
    }

    public function test_the_interval_must_respect_the_configured_minimum(): void
    {
        config(['monitoring.min_interval_seconds' => 30]);

        $response = $this->actingAs($this->user())->post(route('monitors.store'), $this->payload([
            'interval_seconds' => 5,
        ]));

        $response->assertSessionHasErrors('interval_seconds');
    }

    public function test_a_user_can_attach_their_own_notification_channels(): void
    {
        $user = $this->user();
        $channel = NotificationChannel::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('monitors.store'), $this->payload([
            'notification_channels' => [$channel->uuid],
        ]))->assertSessionHasNoErrors();

        $this->assertTrue(Monitor::first()->notificationChannels->contains($channel));
    }

    public function test_a_user_cannot_attach_someone_elses_channel(): void
    {
        $channel = NotificationChannel::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->user())->post(route('monitors.store'), $this->payload([
            'notification_channels' => [$channel->uuid],
        ]));

        $response->assertSessionHasErrors('notification_channels.0');
    }

    public function test_a_user_can_update_their_monitor(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->put(route('monitors.update', $monitor), $this->payload([
            'name' => 'Renamed',
            'interval_seconds' => 60,
        ]));

        $response->assertRedirect(route('monitors.show', $monitor));
        $this->assertSame('Renamed', $monitor->fresh()->name);
        $this->assertSame(60, $monitor->fresh()->interval_seconds);
    }

    public function test_a_user_cannot_update_someone_elses_monitor(): void
    {
        $monitor = Monitor::factory()->forUser(User::factory()->withRole('User')->create())->create();

        $this->actingAs($this->user())
            ->put(route('monitors.update', $monitor), $this->payload())
            ->assertForbidden();
    }

    public function test_a_user_can_delete_their_monitor(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->delete(route('monitors.destroy', $monitor));

        $response->assertRedirect(route('monitors.index'));
        $this->assertSame(0, Monitor::count());
    }

    public function test_a_user_cannot_delete_someone_elses_monitor(): void
    {
        $monitor = Monitor::factory()->forUser(User::factory()->withRole('User')->create())->create();

        $this->actingAs($this->user())
            ->delete(route('monitors.destroy', $monitor))
            ->assertForbidden();

        $this->assertSame(1, Monitor::count());
    }

    public function test_deleting_a_monitor_removes_its_checks_and_incidents(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();
        MonitorCheck::factory()->create(['monitor_id' => $monitor->id]);
        Incident::factory()->create(['monitor_id' => $monitor->id]);

        $this->actingAs($user)->delete(route('monitors.destroy', $monitor));

        $this->assertSame(0, MonitorCheck::count());
        $this->assertSame(0, Incident::count());
    }

    public function test_the_index_can_be_filtered_by_status(): void
    {
        $user = $this->user();
        $up = Monitor::factory()->forUser($user)->up()->create(['name' => 'Healthy service']);
        $down = Monitor::factory()->forUser($user)->down()->create(['name' => 'Broken service']);

        $response = $this->actingAs($user)->get(route('monitors.index', ['status' => 'down']));

        $response->assertOk()
            ->assertSee($down->name)
            ->assertDontSee($up->name);
    }

    public function test_the_index_can_be_searched(): void
    {
        $user = $this->user();
        Monitor::factory()->forUser($user)->create(['name' => 'Billing API']);
        Monitor::factory()->forUser($user)->create(['name' => 'Marketing site']);

        $response = $this->actingAs($user)->get(route('monitors.index', ['search' => 'Billing']));

        $response->assertOk()
            ->assertSee('Billing API')
            ->assertDontSee('Marketing site');
    }
}
