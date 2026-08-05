<?php

namespace Tests\Feature\StatusPages;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PublicStatusPageTest extends TestCase
{
    use RefreshDatabase;

    private function page(array $attributes = []): StatusPage
    {
        return StatusPage::factory()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'slug' => 'acme',
        ], $attributes));
    }

    public function test_a_published_page_is_visible_without_logging_in(): void
    {
        $this->page(['title' => 'Acme Status']);

        $this->get(route('status.show', 'acme'))
            ->assertOk()
            ->assertSee('Acme Status');
    }

    public function test_an_unpublished_page_returns_404(): void
    {
        $this->page(['is_published' => false]);

        $this->get(route('status.show', 'acme'))->assertNotFound();
    }

    public function test_an_unknown_slug_returns_404(): void
    {
        $this->get(route('status.show', 'nope'))->assertNotFound();
    }

    public function test_it_reports_each_monitor_status_and_uptime(): void
    {
        $page = $this->page();
        $monitor = Monitor::factory()->forUser(User::find($page->user_id))->up()->create([
            'name' => 'Checkout API',
        ]);
        $page->monitors()->attach($monitor);

        MonitorCheck::factory()->count(9)->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subHour(),
        ]);
        MonitorCheck::factory()->down()->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subHour(),
        ]);

        $this->get(route('status.show', 'acme'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('status/Show')
                ->where('overall', 'up')
                ->where('monitors.0.name', 'Checkout API')
                ->where('monitors.0.status', 'up')
                ->where('monitors.0.uptime_percentage', 90)
            );
    }

    public function test_the_overall_status_is_down_when_any_monitor_is_down(): void
    {
        $page = $this->page();
        $owner = User::find($page->user_id);

        $page->monitors()->attach(Monitor::factory()->forUser($owner)->up()->create());
        $page->monitors()->attach(Monitor::factory()->forUser($owner)->down()->create());

        $this->get(route('status.show', 'acme'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('overall', 'down'));
    }

    /**
     * The public page is unauthenticated, so it must never leak the target
     * being checked or the error text from a failing check.
     */
    public function test_it_does_not_expose_monitor_targets_or_errors(): void
    {
        $page = $this->page();
        $monitor = Monitor::factory()->forUser(User::find($page->user_id))->down()->create([
            'name' => 'Internal API',
            'url' => 'https://secret-internal.example.com/health',
        ]);
        $page->monitors()->attach($monitor);

        MonitorCheck::factory()->down()->create([
            'monitor_id' => $monitor->id,
            'error' => 'Database credentials rejected',
            'checked_at' => now(),
        ]);

        $response = $this->get(route('status.show', 'acme'));

        $response->assertOk()
            ->assertSee('Internal API')
            ->assertDontSee('secret-internal.example.com')
            ->assertDontSee('Database credentials rejected');
    }

    public function test_it_renders_the_owners_house_style(): void
    {
        StatusPage::factory()->themed()->create([
            'user_id' => User::factory()->create()->id,
            'slug' => 'acme',
        ]);

        $this->get(route('status.show', 'acme'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('theme.brand_color', '#ff6600')
                ->where('theme.radius', 12)
                ->where('theme.logo_url', 'https://acme.test/logo.svg')
                ->where('theme.links.0.label', 'Website')
                // Built on the server so the first painted frame is already in
                // the owner's colours rather than the app's defaults.
                ->where('themeCss', fn (string $css) => str_contains($css, '--sp-bg:#101820'))
            );
    }

    public function test_an_untouched_page_still_gets_a_complete_theme(): void
    {
        $this->page();

        $this->get(route('status.show', 'acme'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('theme.brand_color', '#4f46e5')
                ->where('theme.links', [])
                ->where('themeCss', fn (string $css) => str_contains($css, '.sp-theme{'))
            );
    }

    public function test_a_page_with_no_monitors_reports_pending(): void
    {
        $this->page();

        $this->get(route('status.show', 'acme'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('overall', 'pending'));
    }
}
