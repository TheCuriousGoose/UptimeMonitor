<?php

namespace Tests\Feature\Content;

use App\Content\MarkdownRenderer;
use App\Enums\ContentType;
use App\Models\ContentEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function admin(): User
    {
        return User::factory()->withRole('Super Admin')->create();
    }

    // -- Public visibility -------------------------------------------------

    public function test_only_published_entries_are_listed(): void
    {
        ContentEntry::factory()->type(ContentType::Post)->create(['title' => 'Live']);
        ContentEntry::factory()->type(ContentType::Post)->draft()->create(['title' => 'Draft']);
        ContentEntry::factory()->type(ContentType::Post)->scheduled()->create(['title' => 'Future']);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('content/Blog')
                ->has('entries', 1)
                ->where('entries.0.title', 'Live'));
    }

    /**
     * A draft must not be reachable by guessing its slug, not merely absent
     * from the index.
     */
    public function test_a_draft_cannot_be_reached_directly(): void
    {
        $draft = ContentEntry::factory()->type(ContentType::Post)->draft()->create();

        $this->get(route('content.show', ['segment' => 'blog', 'slug' => $draft->slug]))
            ->assertNotFound();
    }

    public function test_a_scheduled_entry_cannot_be_reached_before_its_date(): void
    {
        $scheduled = ContentEntry::factory()->type(ContentType::Post)->scheduled()->create();

        $this->get(route('content.show', ['segment' => 'blog', 'slug' => $scheduled->slug]))
            ->assertNotFound();
    }

    public function test_an_entry_cannot_be_read_under_the_wrong_segment(): void
    {
        $post = ContentEntry::factory()->type(ContentType::Post)->create();

        $this->get(route('content.show', ['segment' => 'docs', 'slug' => $post->slug]))
            ->assertNotFound();
    }

    public function test_a_published_entry_renders_with_its_body(): void
    {
        $entry = ContentEntry::factory()->type(ContentType::Doc)->create([
            'body' => "# Title\n\nSome **bold** copy.",
        ]);

        $this->get(route('content.show', ['segment' => 'docs', 'slug' => $entry->slug]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('content/Show')
                ->where('entry.uuid', $entry->uuid)
                ->where('bodyHtml', fn (string $html) => str_contains($html, '<strong>bold</strong>')));
    }

    public function test_docs_are_returned_in_manual_reading_order(): void
    {
        ContentEntry::factory()->type(ContentType::Doc)->create([
            'category' => 'Basics', 'sort_order' => 2, 'title' => 'Second',
        ]);
        ContentEntry::factory()->type(ContentType::Doc)->create([
            'category' => 'Basics', 'sort_order' => 1, 'title' => 'First',
        ]);

        $this->get(route('docs.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('entries.0.title', 'First')
                ->where('entries.1.title', 'Second'));
    }

    // -- Markdown safety ---------------------------------------------------

    /**
     * The rendered HTML is injected with v-html, so an author must not be
     * able to land script in a reader's page.
     */
    public function test_raw_html_and_unsafe_links_are_stripped(): void
    {
        $html = app(MarkdownRenderer::class)->toHtml(
            "# Safe\n\n<script>alert('xss')</script>\n\n<img src=x onerror=alert(1)>\n\n[click](javascript:alert(1))",
        );

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('<h1>Safe</h1>', $html);
    }

    // -- Admin CRUD --------------------------------------------------------

    public function test_an_admin_can_create_an_entry(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.content.store'), [
                'type' => 'doc',
                'title' => 'Getting started',
                'slug' => '',
                'body' => '# Hello',
                'category' => 'Basics',
            ])
            ->assertRedirect(route('admin.content.index'));

        $entry = ContentEntry::sole();

        $this->assertSame('getting-started', $entry->slug, 'A blank slug should derive from the title.');
        $this->assertSame($admin->id, $entry->author_id);
        $this->assertNull($entry->published_at, 'No publish date should leave it a draft.');
    }

    public function test_fields_belonging_to_another_type_are_dropped(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.content.store'), [
            'type' => 'doc',
            'title' => 'A doc',
            'body' => 'Body',
            'category' => 'Basics',
            // Only changelog entries carry a version.
            'version' => 'v9.9.9',
        ])->assertRedirect();

        $this->assertNull(ContentEntry::sole()->version);
    }

    public function test_slugs_are_unique_per_type_but_may_repeat_across_types(): void
    {
        $admin = $this->admin();

        ContentEntry::factory()->type(ContentType::Doc)->create(['slug' => 'shared']);

        // Same slug, different type — allowed.
        $this->actingAs($admin)->post(route('admin.content.store'), [
            'type' => 'post',
            'title' => 'Shared',
            'slug' => 'shared',
            'body' => 'Body',
        ])->assertRedirect();

        // Same slug, same type — rejected.
        $this->actingAs($admin)->post(route('admin.content.store'), [
            'type' => 'doc',
            'title' => 'Shared again',
            'slug' => 'shared',
            'body' => 'Body',
        ])->assertSessionHasErrors('slug');

        $this->assertSame(2, ContentEntry::count());
    }

    public function test_an_entry_can_be_updated_and_deleted(): void
    {
        $admin = $this->admin();
        $entry = ContentEntry::factory()->type(ContentType::Post)->create(['title' => 'Before']);

        $this->actingAs($admin)->put(route('admin.content.update', $entry), [
            'type' => 'post',
            'title' => 'After',
            'slug' => $entry->slug,
            'body' => 'Updated body',
        ])->assertRedirect();

        $this->assertSame('After', $entry->fresh()->title);

        $this->actingAs($admin)
            ->delete(route('admin.content.destroy', $entry))
            ->assertRedirect();

        $this->assertDatabaseMissing('content_entries', ['id' => $entry->id]);
    }

    public function test_a_regular_user_cannot_reach_the_admin_content_screen(): void
    {
        $user = User::factory()->withRole('User')->create();

        $this->actingAs($user)->get(route('admin.content.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.content.store'), [
            'type' => 'post', 'title' => 'Nope', 'body' => 'x',
        ])->assertForbidden();

        $this->assertSame(0, ContentEntry::count());
    }

    public function test_guests_cannot_reach_the_admin_content_screen(): void
    {
        $this->get(route('admin.content.index'))->assertRedirect(route('login'));
    }
}
