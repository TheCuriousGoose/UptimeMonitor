<?php

namespace Tests\Feature\Api\V1;

use App\Models\Incident;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IncidentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        RateLimiter::clear('api');
    }

    private function user(): User
    {
        return User::factory()->withRole('User')->create();
    }

    public function test_incidents_are_scoped_to_the_tokens_owner(): void
    {
        $user = $this->user();
        $mine = Monitor::factory()->forUser($user)->create();
        $theirs = Monitor::factory()->forUser($this->user())->create();

        Incident::factory()->count(2)->create(['monitor_id' => $mine->id]);
        Incident::factory()->count(3)->create(['monitor_id' => $theirs->id]);

        Sanctum::actingAs($user, ['incidents:read']);

        $this->getJson(route('api.v1.incidents.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['uuid', 'started_at', 'cause', 'is_ongoing', 'monitor']],
            ]);
    }

    public function test_incidents_can_be_filtered_to_ongoing(): void
    {
        $user = $this->user();
        $monitor = Monitor::factory()->forUser($user)->create();

        Incident::factory()->create(['monitor_id' => $monitor->id]);
        Incident::factory()->resolved()->create(['monitor_id' => $monitor->id]);

        Sanctum::actingAs($user, ['incidents:read']);

        $this->getJson(route('api.v1.incidents.index', ['ongoing' => true]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_ongoing', true);
    }

    public function test_a_monitors_read_token_cannot_read_incidents(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user, ['monitors:read']);

        $this->getJson(route('api.v1.incidents.index'))->assertForbidden();
    }

    public function test_another_users_incident_cannot_be_read(): void
    {
        $owner = $this->user();
        $attacker = $this->user();
        $monitor = Monitor::factory()->forUser($owner)->create();
        $incident = Incident::factory()->create(['monitor_id' => $monitor->id]);

        Sanctum::actingAs($attacker, ['incidents:read']);

        $this->getJson(route('api.v1.incidents.show', $incident))->assertForbidden();
    }
}
