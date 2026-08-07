<?php

namespace Tests\Feature\Settings;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Settings\SettingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function settings(): SettingRepository
    {
        $repository = app(SettingRepository::class);
        $repository->flush();

        return $repository;
    }

    private function configureGoogle(): void
    {
        $settings = $this->settings();
        $settings->set('oauth.google.client_id', 'client-id');
        $settings->set('oauth.google.client_secret', 'client-secret');
        $settings->set('oauth.google', true);
        $settings->flush();
    }

    public function test_credentials_are_nested_under_their_provider(): void
    {
        $google = $this->settings()->tree()->firstWhere('key', 'oauth.google');

        $this->assertSame(
            ['oauth.google.client_id', 'oauth.google.client_secret', 'oauth.google.redirect'],
            array_column($google['children'], 'key'),
        );
    }

    public function test_secrets_are_encrypted_at_rest_and_never_returned(): void
    {
        $this->settings()->set('oauth.github.client_secret', 'hunter2');

        $stored = Setting::where('key', 'oauth.github.client_secret')->value('value');

        $this->assertNotSame('hunter2', $stored);
        $this->assertSame('hunter2', $this->settings()->get('oauth.github.client_secret'));

        $presented = $this->settings()->tree()
            ->firstWhere('key', 'oauth.github')['children'];
        $secret = collect($presented)->firstWhere('key', 'oauth.github.client_secret');

        $this->assertNull($secret['value']);
        $this->assertTrue($secret['has_value']);
    }

    public function test_submitting_a_blank_secret_keeps_the_stored_one(): void
    {
        $this->settings()->set('oauth.github.client_secret', 'keep-me');

        $admin = User::factory()->create();
        $admin->syncRoles(Role::SuperAdmin->value);

        $this->actingAs($admin)
            ->put(route('admin.settings.update', 'oauth.github.client_secret'), ['value' => ''])
            ->assertRedirect();

        $this->assertSame('keep-me', $this->settings()->get('oauth.github.client_secret'));
    }

    public function test_toggling_a_provider_persists_both_ways(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(Role::SuperAdmin->value);

        $this->actingAs($admin)
            ->put(route('admin.settings.update', 'oauth.google'), ['value' => '1'])
            ->assertRedirect();

        $this->assertTrue($this->settings()->get('oauth.google'));

        $this->actingAs($admin)
            ->put(route('admin.settings.update', 'oauth.google'), ['value' => '0'])
            ->assertRedirect();

        $this->assertFalse($this->settings()->get('oauth.google'));
    }

    public function test_provider_is_only_usable_once_enabled_and_configured(): void
    {
        $this->assertFalse($this->settings()->oauthUsable('google'));

        $this->settings()->set('oauth.google', true);
        $this->assertFalse($this->settings()->oauthUsable('google'));

        $this->configureGoogle();
        $this->assertTrue($this->settings()->oauthUsable('google'));
    }

    public function test_redirect_is_refused_while_the_provider_is_disabled(): void
    {
        $this->get(route('oauth.redirect', 'google'))->assertNotFound();
    }

    public function test_redirect_works_once_configured_in_the_database(): void
    {
        $this->configureGoogle();

        $this->get(route('oauth.redirect', 'google'))
            ->assertRedirectContains('accounts.google.com');
    }

    public function test_credentials_are_not_shared_with_the_browser(): void
    {
        $this->configureGoogle();

        $shared = $this->settings()->authenticationSettings();
        $keys = $shared->pluck('key')->all();

        $this->assertContains('oauth.google', $keys);
        $this->assertNotContains('oauth.google.client_id', $keys);
        $this->assertNotContains('oauth.google.client_secret', $keys);
    }
}
