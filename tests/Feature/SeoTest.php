<?php

namespace Tests\Feature;

use App\Enums\ContentType;
use App\Models\ContentEntry;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The root template used to send "noindex, nofollow" on every response, which
 * also covered the marketing site, the docs and every public status page —
 * the pages a customer is most likely to link to.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public static function publicRoutes(): array
    {
        return [
            'home' => ['/'],
            'features' => ['/features'],
            'about' => ['/about'],
            'contact' => ['/contact'],
            'roadmap' => ['/roadmap'],
            'docs' => ['/docs'],
            'blog' => ['/blog'],
            'changelog' => ['/changelog'],
        ];
    }

    #[DataProvider('publicRoutes')]
    public function test_public_pages_are_indexable(string $url): void
    {
        $response = $this->get($url);

        $response->assertOk()
            ->assertDontSee('noindex', escape: false)
            ->assertSee('rel="canonical"', escape: false);
    }

    public function test_a_public_status_page_is_indexable(): void
    {
        $page = StatusPage::factory()->create(['is_published' => true]);

        $this->get(route('status.show', $page->slug))
            ->assertOk()
            ->assertDontSee('noindex', escape: false);
    }

    public function test_the_authenticated_app_stays_out_of_the_index(): void
    {
        $user = User::factory()->withRole('User')->create();

        foreach ([route('dashboard'), route('monitors.index'), route('profile.edit')] as $url) {
            $this->actingAs($user)->get($url)
                ->assertOk()
                ->assertSee('noindex', escape: false);
        }
    }

    public function test_the_login_page_stays_out_of_the_index(): void
    {
        $this->get(route('login'))->assertSee('noindex', escape: false);
    }

    public function test_the_sitemap_lists_public_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml');

        $response->assertSee(route('home'), escape: false);
        $response->assertSee(route('features'), escape: false);
    }

    public function test_the_sitemap_includes_published_content_and_status_pages(): void
    {
        $entry = ContentEntry::factory()->create([
            'type' => ContentType::Doc,
            'slug' => 'seo-fixture-doc',
            'published_at' => now()->subDay(),
        ]);

        $page = StatusPage::factory()->create(['is_published' => true]);

        $this->get('/sitemap.xml')
            ->assertSee(route('content.show', ['segment' => 'docs', 'slug' => $entry->slug]), escape: false)
            ->assertSee(route('status.show', $page->slug), escape: false);
    }

    /**
     * Listing an unreachable URL is worse than omitting it — both of these
     * 404, so advertising them wastes crawl budget on dead links.
     */
    public function test_the_sitemap_omits_unpublished_records(): void
    {
        $draft = ContentEntry::factory()->create([
            'type' => ContentType::Doc,
            'slug' => 'seo-fixture-draft',
            'published_at' => null,
        ]);

        $hidden = StatusPage::factory()->create(['is_published' => false]);

        $response = $this->get('/sitemap.xml');

        $response->assertDontSee($draft->slug, escape: false);
        $response->assertDontSee(route('status.show', $hidden->slug), escape: false);
    }
}
