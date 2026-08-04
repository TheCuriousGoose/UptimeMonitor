<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Telescope is a require-dev package. If its provider is listed in
 * bootstrap/providers.php, `composer install --no-dev` fatals during
 * package:discover because the parent class is not installed.
 */
class ProviderRegistrationTest extends TestCase
{
    public function test_dev_only_providers_are_not_registered_at_bootstrap(): void
    {
        $providers = require base_path('bootstrap/providers.php');

        foreach ($providers as $provider) {
            $this->assertStringNotContainsStringIgnoringCase(
                'telescope',
                $provider,
                'Telescope must be registered conditionally, not from bootstrap/providers.php.',
            );
        }
    }

    /**
     * The conditional registration is environment gated, so a non-local
     * environment must never pull Telescope into the container.
     */
    public function test_telescope_is_not_registered_outside_local(): void
    {
        $this->assertFalse($this->app->environment('local'));

        $registered = array_map(
            fn ($provider) => is_object($provider) ? $provider::class : (string) $provider,
            $this->app->getLoadedProviders() ? array_keys($this->app->getLoadedProviders()) : [],
        );

        foreach ($registered as $provider) {
            $this->assertStringNotContainsStringIgnoringCase('Telescope', $provider);
        }
    }

    public function test_the_application_boots_with_the_registered_providers(): void
    {
        $this->get(route('home'))->assertOk();
    }
}
