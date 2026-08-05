<?php

namespace Tests\Feature\StatusPages;

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\User;
use App\StatusPages\StatusPageTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StatusPageTest extends TestCase
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
            'title' => 'Acme Status',
            'slug' => 'acme',
            'description' => 'Live availability.',
            'is_published' => true,
        ], $overrides);
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('status-pages.index'))->assertRedirect(route('login'));
    }

    public function test_a_user_only_sees_their_own_pages(): void
    {
        $user = $this->user();
        StatusPage::factory()->create(['user_id' => $user->id, 'title' => 'Mine']);
        StatusPage::factory()->create(['user_id' => User::factory()->create()->id, 'title' => 'Theirs']);

        $this->actingAs($user)->get(route('status-pages.index'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Theirs');
    }

    public function test_a_user_can_create_a_status_page_with_monitors(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        $this->actingAs($user)->post(route('status-pages.store'), $this->payload([
            'monitors' => [$monitor->uuid],
        ]))->assertRedirect(route('status-pages.index'));

        $page = StatusPage::first();

        $this->assertSame('acme', $page->slug);
        $this->assertTrue($page->monitors->contains($monitor));
    }

    public function test_the_slug_must_be_url_safe(): void
    {
        $this->actingAs($this->user())
            ->post(route('status-pages.store'), $this->payload(['slug' => 'Not A Slug!']))
            ->assertSessionHasErrors('slug');
    }

    public function test_the_slug_must_be_unique(): void
    {
        StatusPage::factory()->create(['slug' => 'acme']);

        $this->actingAs($this->user())
            ->post(route('status-pages.store'), $this->payload())
            ->assertSessionHasErrors('slug');
    }

    public function test_a_page_can_keep_its_own_slug_when_updated(): void
    {
        $user = $this->user();
        $page = StatusPage::factory()->create(['user_id' => $user->id, 'slug' => 'acme']);

        $this->actingAs($user)
            ->put(route('status-pages.update', $page), $this->payload(['title' => 'Renamed']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $page->fresh()->title);
    }

    public function test_a_user_cannot_add_someone_elses_monitor(): void
    {
        $monitor = Monitor::factory()->forUser(User::factory()->create())->create();

        $this->actingAs($this->user())
            ->post(route('status-pages.store'), $this->payload(['monitors' => [$monitor->uuid]]))
            ->assertSessionHasErrors('monitors.0');
    }

    public function test_monitors_keep_the_order_they_were_selected_in(): void
    {
        $user = $this->user();
        $first = Monitor::factory()->forUser($user)->create(['name' => 'First']);
        $second = Monitor::factory()->forUser($user)->create(['name' => 'Second']);

        $this->actingAs($user)->post(route('status-pages.store'), $this->payload([
            'monitors' => [$second->uuid, $first->uuid],
        ]));

        $this->assertSame(
            ['Second', 'First'],
            StatusPage::first()->monitors->pluck('name')->all(),
        );
    }

    public function test_submitting_an_empty_monitor_list_clears_the_selection(): void
    {
        $user = $this->user();
        $page = StatusPage::factory()->create(['user_id' => $user->id]);
        $monitor = Monitor::factory()->forUser($user)->create();
        $page->monitors()->attach($monitor);

        $this->actingAs($user)->put(route('status-pages.update', $page), $this->payload([
            'slug' => $page->slug,
            'monitors' => [''],
        ]))->assertSessionHasNoErrors();

        $this->assertCount(0, $page->fresh()->monitors);
    }

    public function test_a_user_can_give_their_page_a_house_style(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('status-pages.store'), $this->payload([
            'theme' => [
                'mode' => 'dark',
                'brand_color' => '#FF6600',
                'font_family' => "'Acme Grotesk', sans-serif",
                'radius' => 12,
                'width' => 1024,
                'logo_url' => 'https://acme.test/logo.svg',
                'links' => [['label' => 'Website', 'url' => 'https://acme.test']],
            ],
        ]))->assertSessionHasNoErrors();

        $theme = StatusPage::first()->resolvedTheme();

        $this->assertSame('dark', $theme->mode->value);
        $this->assertSame('#ff6600', $theme->brandColor);
        $this->assertSame(12, $theme->radius);
        $this->assertSame([['label' => 'Website', 'url' => 'https://acme.test']], $theme->links);
    }

    /**
     * The column always holds a complete theme, whatever subset of keys the
     * form happened to send, so nothing downstream has to guess at defaults.
     */
    public function test_a_partial_theme_is_stored_complete(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('status-pages.store'), $this->payload([
            'theme' => ['brand_color' => '#ff6600'],
        ]))->assertSessionHasNoErrors();

        $stored = StatusPage::first()->theme;

        $this->assertSame('#ff6600', $stored['brand_color']);
        $this->assertSame(StatusPageTheme::DEFAULT_WIDTH, $stored['width']);
        $this->assertArrayHasKey('footer_text', $stored);
    }

    public function test_a_page_saved_without_a_theme_falls_back_to_the_defaults(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('status-pages.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $page = StatusPage::first();

        $this->assertNull($page->theme);
        $this->assertSame(StatusPageTheme::DEFAULT_BRAND, $page->resolvedTheme()->brandColor);
    }

    #[DataProvider('invalidThemeFields')]
    public function test_it_rejects_theme_values_it_cannot_render_safely(string $key, mixed $value): void
    {
        $this->actingAs($this->user())
            ->post(route('status-pages.store'), $this->payload(['theme' => [$key => $value]]))
            ->assertSessionHasErrors("theme.{$key}");
    }

    public static function invalidThemeFields(): array
    {
        return [
            'not a colour' => ['brand_color', 'rebeccapurple'],
            'colour with a rule attached' => ['background', '#fff;}body{display:none}'],
            'unknown mode' => ['mode', 'sepia'],
            'radius out of range' => ['radius', 400],
            'width out of range' => ['width', 12],
            'a stylesheet instead of a font' => ['font_url', 'https://evil.test/fonts.css'],
            'a javascript logo' => ['logo_url', 'javascript:alert(1)'],
            'a relative favicon' => ['favicon_url', '/favicon.ico'],
        ];
    }

    public function test_it_rejects_more_links_than_a_page_may_carry(): void
    {
        $links = array_fill(0, StatusPageTheme::MAX_LINKS + 1, [
            'label' => 'Website',
            'url' => 'https://acme.test',
        ]);

        $this->actingAs($this->user())
            ->post(route('status-pages.store'), $this->payload(['theme' => ['links' => $links]]))
            ->assertSessionHasErrors('theme.links');
    }

    public function test_a_user_cannot_update_someone_elses_page(): void
    {
        $page = StatusPage::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($this->user())
            ->put(route('status-pages.update', $page), $this->payload(['slug' => $page->slug]))
            ->assertForbidden();
    }

    public function test_a_user_can_delete_their_page(): void
    {
        $user = $this->user();
        $page = StatusPage::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete(route('status-pages.destroy', $page))
            ->assertRedirect(route('status-pages.index'));

        $this->assertSame(0, StatusPage::count());
    }

    public function test_a_user_cannot_delete_someone_elses_page(): void
    {
        $page = StatusPage::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($this->user())->delete(route('status-pages.destroy', $page))->assertForbidden();
        $this->assertSame(1, StatusPage::count());
    }
}
