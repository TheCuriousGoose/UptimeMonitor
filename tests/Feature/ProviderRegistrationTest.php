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

    public function test_the_telescope_provider_is_only_wired_up_when_installed(): void
    {
        $source = file_get_contents(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringContainsString(
            'class_exists(\Laravel\Telescope\Telescope::class)',
            $source,
            'The conditional registration must guard on the package being present.',
        );
    }

    public function test_the_application_boots_with_the_registered_providers(): void
    {
        $this->get(route('home'))->assertOk();
    }
}
