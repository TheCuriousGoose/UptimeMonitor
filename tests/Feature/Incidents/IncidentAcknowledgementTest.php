<?php

namespace Tests\Feature\Incidents;

use App\Checkers\CheckResult;
use App\Jobs\SendAlert;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Monitoring\StatusEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IncidentAcknowledgementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Queue::fake();
    }

    private function user(): User
    {
        return User::factory()->withRole('User')->create();
    }

    private function incidentFor(User $user): Incident
    {
        $monitor = Monitor::factory()->forUser($user)->create();

        return Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'started_at' => now()->subHour(),
            'resolved_at' => null,
        ]);
    }

    public function test_the_owner_can_acknowledge_an_incident(): void
    {
        $user = $this->user();
        $incident = $this->incidentFor($user);

        $this->actingAs($user)
            ->post(route('incidents.acknowledge.store', $incident))
            ->assertRedirect();

        $incident->refresh();

        $this->assertTrue($incident->isAcknowledged());
        $this->assertSame($user->id, $incident->acknowledged_by);
    }

    public function test_a_note_is_stored_as_a_private_update(): void
    {
        $user = $this->user();
        $incident = $this->incidentFor($user);

        $this->actingAs($user)->post(
            route('incidents.acknowledge.store', $incident),
            ['note' => 'Paging the database team.'],
        );

        $update = $incident->fresh()->updates->first();

        $this->assertNotNull($update);
        $this->assertFalse($update->is_public);
        $this->assertSame('Paging the database team.', $update->body);
    }

    public function test_a_stranger_cannot_acknowledge(): void
    {
        $incident = $this->incidentFor($this->user());

        $this->actingAs($this->user())
            ->post(route('incidents.acknowledge.store', $incident))
            ->assertForbidden();
    }

    public function test_acknowledgement_can_be_removed(): void
    {
        $user = $this->user();
        $incident = $this->incidentFor($user);
        $incident->update(['acknowledged_at' => now(), 'acknowledged_by' => $user->id]);

        $this->actingAs($user)
            ->delete(route('incidents.acknowledge.destroy', $incident))
            ->assertRedirect();

        $incident->refresh();

        $this->assertFalse($incident->isAcknowledged());
        $this->assertNull($incident->acknowledged_by);
    }

    /**
     * The whole contract of an ack: a human has this, stop paging.
     */
    public function test_an_acknowledged_incident_stops_reminding(): void
    {
        $user = $this->user();
        NotificationChannel::factory()->for($user, 'user')->create(['renotify_minutes' => 10]);

        $monitor = Monitor::factory()->forUser($user)->create([
            'confirmation_threshold' => 1,
            'latest_is_up' => true,
        ]);

        app(StatusEvaluator::class)->record(
            $monitor,
            CheckResult::down('boom', 10),
        );

        $incident = $monitor->fresh()->ongoingIncident();
        $incident->update(['acknowledged_at' => now(), 'acknowledged_by' => $user->id]);

        Queue::fake();

        $this->travel(30)->minutes();
        $this->artisan('monitors:sweep-alerts')->assertSuccessful();

        Queue::assertNotPushed(SendAlert::class);
    }

    public function test_the_show_page_is_scoped_to_the_owner(): void
    {
        $user = $this->user();
        $incident = $this->incidentFor($user);

        $this->actingAs($user)->get(route('incidents.show', $incident))->assertOk();
        $this->actingAs($this->user())->get(route('incidents.show', $incident))->assertForbidden();
    }
}
