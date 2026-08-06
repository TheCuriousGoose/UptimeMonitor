<?php

namespace Tests\Feature\StatusPages;

use App\Enums\IncidentUpdateStatus;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicIncidentTimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private StatusPage $page;

    private Monitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->user = User::factory()->withRole('User')->create();

        $this->monitor = Monitor::factory()->forUser($this->user)->create([
            'name' => 'Checkout API',
            'url' => 'https://internal.example.com/secret-path',
        ]);

        $this->page = StatusPage::factory()->create([
            'user_id' => $this->user->id,
            'is_published' => true,
            'show_incidents' => true,
        ]);

        $this->page->monitors()->attach($this->monitor);
    }

    private function incident(array $attributes = []): Incident
    {
        return Incident::factory()->create(array_merge([
            'monitor_id' => $this->monitor->id,
            'started_at' => now()->subHours(2),
            'resolved_at' => now()->subHour(),
            'cause' => 'Expected HTTP 200, got 500',
            'failed_checks' => 7,
        ], $attributes));
    }

    private function props(): array
    {
        return $this->get(route('status.show', $this->page->slug))
            ->viewData('page')['props'];
    }

    public function test_a_public_update_appears_on_the_page(): void
    {
        $incident = $this->incident();

        IncidentUpdate::factory()->public(IncidentUpdateStatus::Identified)->create([
            'incident_id' => $incident->id,
            'body' => 'A bad deploy. Rolling back.',
        ]);

        $incidents = $this->props()['incidents'];

        $this->assertCount(1, $incidents);
        $this->assertSame('identified', $incidents[0]['updates'][0]['status']);
        $this->assertStringContainsString('Rolling back', $incidents[0]['updates'][0]['body_html']);
    }

    /**
     * The whole point of the boundary: assert the exact key set so a future
     * field cannot be added into the public payload by accident.
     */
    public function test_the_payload_exposes_nothing_but_the_agreed_keys(): void
    {
        $incident = $this->incident();
        IncidentUpdate::factory()->public()->create(['incident_id' => $incident->id]);

        $published = $this->props()['incidents'][0];

        $this->assertSame(
            ['monitor', 'started_at', 'resolved_at', 'duration_seconds', 'is_resolved', 'updates'],
            array_keys($published),
        );

        $this->assertSame(
            ['status', 'body_html', 'published_at'],
            array_keys($published['updates'][0]),
        );
    }

    public function test_it_never_leaks_the_cause_url_or_check_count(): void
    {
        $incident = $this->incident();
        IncidentUpdate::factory()->public()->create(['incident_id' => $incident->id]);

        $response = $this->get(route('status.show', $this->page->slug));

        $response->assertDontSee('Expected HTTP 200, got 500', escape: false);
        $response->assertDontSee('secret-path', escape: false);
        $response->assertDontSee('failed_checks', escape: false);
    }

    public function test_a_private_note_never_appears(): void
    {
        $incident = $this->incident();

        IncidentUpdate::factory()->create([
            'incident_id' => $incident->id,
            'body' => 'Paging the on-call DBA',
            'is_public' => false,
        ]);

        $response = $this->get(route('status.show', $this->page->slug));

        $response->assertDontSee('Paging the on-call DBA', escape: false);
        $this->assertSame([], $this->props()['incidents']);
    }

    public function test_an_incident_with_no_public_update_is_absent_entirely(): void
    {
        $this->incident();

        $this->assertSame([], $this->props()['incidents']);
    }

    public function test_maintenance_incidents_are_absent(): void
    {
        $incident = $this->incident(['is_maintenance' => true]);
        IncidentUpdate::factory()->public()->create(['incident_id' => $incident->id]);

        $this->assertSame([], $this->props()['incidents']);
    }

    public function test_a_page_with_the_toggle_off_publishes_no_timeline(): void
    {
        $this->page->update(['show_incidents' => false]);

        $incident = $this->incident();
        IncidentUpdate::factory()->public()->create(['incident_id' => $incident->id]);

        $this->assertNull($this->props()['incidents']);
    }

    public function test_the_author_name_is_never_published(): void
    {
        $author = User::factory()->create(['name' => 'Alice Operator']);
        $incident = $this->incident();

        IncidentUpdate::factory()->public()->create([
            'incident_id' => $incident->id,
            'user_id' => $author->id,
        ]);

        $this->get(route('status.show', $this->page->slug))
            ->assertDontSee('Alice Operator', escape: false);
    }

    public function test_raw_html_in_an_update_is_stripped(): void
    {
        $incident = $this->incident();

        IncidentUpdate::factory()->public()->create([
            'incident_id' => $incident->id,
            'body' => 'Fixed. <script>alert(1)</script>',
        ]);

        $html = $this->props()['incidents'][0]['updates'][0]['body_html'];

        $this->assertStringNotContainsString('<script>', $html);
    }
}
