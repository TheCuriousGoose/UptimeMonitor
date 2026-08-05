<?php

namespace Tests\Feature\Monitors;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\NotificationChannel;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Resource collections wrap themselves in a "data" envelope by default, which
 * the Vue pages do not expect — they receive plain lists and objects. These
 * assertions fail loudly if that envelope ever creeps back in, because the UI
 * breaks silently otherwise.
 */
class InertiaPropShapeTest extends TestCase
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

    private function props(TestResponse $response): array
    {
        return $response->viewData('page')['props'];
    }

    private function assertUnwrapped(mixed $value, string $label): void
    {
        $this->assertIsArray($value, "[{$label}] should be an array.");
        $this->assertArrayNotHasKey('data', $value, "[{$label}] must not be wrapped in a data envelope.");
    }

    public function test_the_monitor_page_props_are_flat(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->up()->create();
        $channel = NotificationChannel::factory()->create(['user_id' => $user->id]);
        $monitor->notificationChannels()->attach($channel);
        MonitorCheck::factory()->create(['monitor_id' => $monitor->id, 'checked_at' => now()]);

        $props = $this->props($this->actingAs($user)->get(route('monitors.show', $monitor)));

        $this->assertUnwrapped($props['monitor'], 'monitor');
        $this->assertUnwrapped($props['checks'], 'checks');
        $this->assertUnwrapped($props['incidents'], 'incidents');
        $this->assertSame($monitor->name, $props['monitor']['name']);
        $this->assertUnwrapped($props['monitor']['notification_channels'], 'monitor.notification_channels');
        $this->assertSame($channel->name, $props['monitor']['notification_channels'][0]['name']);
        $this->assertCount(1, $props['checks']);
    }

    public function test_the_create_page_props_are_flat(): void
    {
        $user = $this->user();
        NotificationChannel::factory()->create(['user_id' => $user->id, 'name' => 'Ops email']);

        $props = $this->props($this->actingAs($user)->get(route('monitors.create')));

        $this->assertUnwrapped($props['channels'], 'channels');
        $this->assertSame('Ops email', $props['channels'][0]['name']);
        $this->assertContains('keyword', $props['types']);
    }

    public function test_the_edit_page_props_are_flat(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        $props = $this->props($this->actingAs($user)->get(route('monitors.edit', $monitor)));

        $this->assertUnwrapped($props['monitor'], 'monitor');
        $this->assertUnwrapped($props['channels'], 'channels');
        $this->assertSame($monitor->uuid, $props['monitor']['uuid']);
    }

    public function test_the_integrations_page_props_are_flat(): void
    {
        $user = $this->user();
        NotificationChannel::factory()->create(['user_id' => $user->id, 'name' => 'Ops email']);

        $props = $this->props($this->actingAs($user)->get(route('integrations.index')));

        $this->assertUnwrapped($props['integrations'], 'integrations');
        $this->assertSame('Ops email', $props['integrations'][0]['name']);
    }

    public function test_the_status_pages_props_are_flat_and_include_selected_monitors(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();
        $page = StatusPage::factory()->create(['user_id' => $user->id]);
        $page->monitors()->attach($monitor);

        $props = $this->props($this->actingAs($user)->get(route('status-pages.index')));

        $this->assertUnwrapped($props['pages'], 'pages');
        $this->assertUnwrapped($props['monitors'], 'monitors');
        $this->assertUnwrapped($props['pages'][0]['monitors'], 'pages.0.monitors');
        $this->assertSame($monitor->uuid, $props['pages'][0]['monitors'][0]['uuid']);
    }

    public function test_the_dashboard_props_are_flat(): void
    {
        $user = $this->user();
        Monitor::factory()->forUser($user)->down()->create();

        $props = $this->props($this->actingAs($user)->get(route('dashboard')));

        $this->assertUnwrapped($props['attention'], 'attention');
        $this->assertUnwrapped($props['recentIncidents'], 'recentIncidents');
        $this->assertArrayHasKey('uptime_percentage', $props['summary']);
    }

    /**
     * The index table is the one place that legitimately keeps the envelope,
     * because the DataTable component reads data/links/meta.
     */
    public function test_the_index_page_keeps_its_pagination_envelope(): void
    {
        $user = $this->user();
        Monitor::factory()->forUser($user)->create();

        $props = $this->props($this->actingAs($user)->get(route('monitors.index')));

        $this->assertArrayHasKey('data', $props['monitors']);
        $this->assertArrayHasKey('meta', $props['monitors']);
    }
}
