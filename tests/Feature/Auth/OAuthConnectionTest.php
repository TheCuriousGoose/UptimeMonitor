<?php

namespace Tests\Feature\Auth;

use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OAuthConnectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Eloquent infers "o_auth_connections" from the OAuth prefix, which does
     * not match the migration. Every social login 500s when these drift apart.
     */
    public function test_model_resolves_to_the_migrated_table(): void
    {
        $table = (new OAuthConnection)->getTable();

        $this->assertSame('oauth_connections', $table);
        $this->assertTrue(
            Schema::hasTable($table),
            "The OAuthConnection model points at [{$table}], which does not exist.",
        );
    }

    public function test_a_connection_can_be_persisted_and_reused(): void
    {
        $user = User::factory()->create();

        $connection = OAuthConnection::updateOrCreate(
            ['provider' => 'github', 'provider_id' => '12345'],
            ['user_id' => $user->id],
        );

        $this->assertDatabaseHas('oauth_connections', [
            'provider' => 'github',
            'provider_id' => '12345',
            'user_id' => $user->id,
        ]);

        // A second callback for the same identity must update, not duplicate.
        OAuthConnection::updateOrCreate(
            ['provider' => 'github', 'provider_id' => '12345'],
            ['user_id' => $user->id],
        );

        $this->assertSame(1, OAuthConnection::query()->count());
        $this->assertTrue($connection->user->is($user));
    }
}
