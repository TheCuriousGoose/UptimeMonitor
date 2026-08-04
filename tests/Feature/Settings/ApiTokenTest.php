<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ApiTokenTest extends TestCase
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

    public function test_a_token_can_be_created_with_chosen_abilities(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('api-tokens.store'), [
                'name' => 'CI pipeline',
                'abilities' => ['monitors:read', 'checks:trigger'],
            ])
            ->assertRedirect(route('api-tokens.index'));

        $token = $user->tokens()->sole();

        $this->assertSame('CI pipeline', $token->name);
        $this->assertSame(['monitors:read', 'checks:trigger'], $token->abilities);
        $this->assertNull($token->expires_at);
    }

    /**
     * Sanctum stores only a hash, so the plaintext has to reach the user in
     * the one flash payload or it is unrecoverable.
     */
    public function test_the_plaintext_token_is_flashed_exactly_once(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('api-tokens.store'), [
            'name' => 'Reveal once',
            'abilities' => ['monitors:read'],
        ]);

        $flashed = session(SessionKey::FLASH_DATA)['apiToken'] ?? null;

        $this->assertNotNull($flashed, 'The created token was not flashed.');
        $this->assertSame('Reveal once', $flashed['name']);
        $this->assertStringContainsString('|', $flashed['token']);

        // A fresh visit must not carry it again — the key is gone entirely,
        // not merely null.
        $this->actingAs($user)
            ->get(route('api-tokens.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->missing('flash.apiToken'));
    }

    public function test_an_expiry_can_be_set(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('api-tokens.store'), [
            'name' => 'Expiring',
            'abilities' => ['monitors:read'],
            'expires_in_days' => 30,
        ]);

        $this->assertTrue(
            $user->tokens()->sole()->expires_at->isBetween(now()->addDays(29), now()->addDays(31)),
        );
    }

    public function test_unknown_abilities_are_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('api-tokens.store'), [
                'name' => 'Sneaky',
                'abilities' => ['monitors:read', 'billing:write'],
            ])
            ->assertSessionHasErrors('abilities.1');

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_at_least_one_ability_is_required(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('api-tokens.store'), ['name' => 'Useless', 'abilities' => []])
            ->assertSessionHasErrors('abilities');
    }

    public function test_a_user_can_revoke_their_own_token(): void
    {
        $user = $this->user();
        $token = $user->createToken('Doomed', ['monitors:read'])->accessToken;

        $this->actingAs($user)
            ->delete(route('api-tokens.destroy', $token->id))
            ->assertRedirect();

        $this->assertSame(0, $user->tokens()->count());
    }

    /**
     * Token ids are sequential, so this must be scoped to the acting user or
     * anyone could revoke another account's keys by guessing.
     */
    public function test_a_user_cannot_revoke_someone_elses_token(): void
    {
        $owner = $this->user();
        $attacker = $this->user();
        $token = $owner->createToken('Not yours', ['monitors:read'])->accessToken;

        $this->actingAs($attacker)
            ->delete(route('api-tokens.destroy', $token->id))
            ->assertNotFound();

        $this->assertSame(1, $owner->tokens()->count());
    }
}
