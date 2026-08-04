<?php

namespace Tests\Feature\Settings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * routes/settings.php was previously required outside the ['auth', 'verified']
 * group in web.php, so every settings page — including password change and
 * API key management — was reachable by guests. This pins the fix.
 */
class SettingsAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function guestBlockedRoutes(): array
    {
        return [
            'profile' => ['GET', 'profile.edit'],
            'security' => ['GET', 'security.edit'],
            'appearance' => ['GET', 'appearance.edit'],
            'api tokens' => ['GET', 'api-tokens.index'],
        ];
    }

    #[DataProvider('guestBlockedRoutes')]
    public function test_guests_are_redirected_away_from_settings_pages(string $method, string $routeName): void
    {
        $response = $this->call($method, route($routeName));

        $response->assertRedirect(route('login'));
    }

    public function test_guests_cannot_create_an_api_token(): void
    {
        $response = $this->post(route('api-tokens.store'), [
            'name' => 'Stolen key',
            'abilities' => ['monitors:read'],
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
