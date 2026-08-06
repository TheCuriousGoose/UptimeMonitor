<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The pages poll themselves with a hardcoded list of prop names. Renaming a
 * prop would not break anything loudly — the poll would just quietly stop
 * refreshing the thing the user is watching, on an uptime dashboard, which is
 * the worst place for a silent failure. These assert the two lists agree.
 */
class LiveDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /**
     * Mirrors the `only` arrays passed to <LiveIndicator> in each page.
     *
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function polledProps(): array
    {
        return [
            'dashboard' => ['Dashboard', ['summary', 'attention', 'recentIncidents']],
            'monitors index' => ['monitors/Index', ['monitors']],
            'monitors show' => ['monitors/Show', ['monitor', 'checks', 'stats', 'series', 'incidents']],
            'incidents index' => ['incidents/Index', ['incidents', 'summary']],
        ];
    }

    private function seedUser(): User
    {
        $user = User::factory()->withRole('User')->create();

        $monitor = Monitor::factory()->forUser($user)->up()->create();

        MonitorCheck::factory()->count(3)->create(['monitor_id' => $monitor->id]);
        Incident::factory()->create(['monitor_id' => $monitor->id]);

        return $user;
    }

    private function urlFor(string $component, User $user): string
    {
        return match ($component) {
            'Dashboard' => route('dashboard'),
            'monitors/Index' => route('monitors.index'),
            'monitors/Show' => route('monitors.show', Monitor::query()->forUser($user)->first()),
            'incidents/Index' => route('incidents.index'),
        };
    }

    private function partialReload(User $user, string $url, string $component, array $only): TestResponse
    {
        return $this->actingAs($user)->get($url, [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
            'X-Inertia-Partial-Component' => $component,
            'X-Inertia-Partial-Data' => implode(',', $only),
        ]);
    }

    /**
     * @param  array<int, string>  $only
     */
    #[DataProvider('polledProps')]
    public function test_every_polled_prop_is_returned_by_its_page(string $component, array $only): void
    {
        $user = $this->seedUser();
        $url = $this->urlFor($component, $user);

        // The full page must expose every name the poll asks for, or the
        // partial reload silently returns nothing for it.
        $props = $this->actingAs($user)->get($url)->viewData('page')['props'];

        foreach ($only as $prop) {
            $this->assertArrayHasKey($prop, $props, "[{$component}] does not provide \"{$prop}\".");
        }
    }

    /**
     * @param  array<int, string>  $only
     */
    #[DataProvider('polledProps')]
    public function test_a_partial_reload_returns_only_the_polled_props(string $component, array $only): void
    {
        $user = $this->seedUser();
        $url = $this->urlFor($component, $user);

        $response = $this->partialReload($user, $url, $component, $only);

        $response->assertOk();

        $payload = $response->json();

        $this->assertSame($component, $payload['component']);

        foreach ($only as $prop) {
            $this->assertArrayHasKey($prop, $payload['props']);
        }

        // Shared props always ride along; what must not appear is any other
        // page prop, because that is the cost the "only" list exists to avoid.
        $unexpected = array_diff(
            array_keys($payload['props']),
            [...$only, 'auth', 'name', 'locale', 'settings', 'sidebarOpen', 'flash', 'errors'],
        );

        $this->assertSame([], array_values($unexpected));
    }
}
