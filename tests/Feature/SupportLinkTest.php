<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The support links are rendered from a shared prop rather than hardcoded in
 * the layouts, so that a self-hosted instance can switch them off instead of
 * pointing its own users at someone else's donation page.
 */
class SupportLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_the_support_url_is_shared_with_every_page(): void
    {
        config(['app.support_url' => 'https://buymeacoffee.com/thecuriousgoose']);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('supportUrl', 'https://buymeacoffee.com/thecuriousgoose')
                ->etc(),
            );
    }

    /**
     * Null rather than an empty string: the layouts test truthiness to decide
     * whether to render the block at all.
     */
    public function test_an_empty_config_hides_the_links(): void
    {
        config(['app.support_url' => '']);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('supportUrl', null)
                ->etc(),
            );
    }
}
