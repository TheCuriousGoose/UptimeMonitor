<?php

namespace Tests\Feature\StatusPages;

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
