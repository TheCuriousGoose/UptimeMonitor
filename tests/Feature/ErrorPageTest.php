<?php

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/**
 * Every status a browser can land on renders the branded Inertia page, while
 * JSON clients keep getting JSON. The two are decided in the same respond()
 * callback in bootstrap/app.php, so they are tested together.
 */
class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function assertRendersErrorPage(TestResponse $response, int $status): void
    {
        $response->assertStatus($status);

        $page = $response->viewData('page');

        $this->assertSame('Error', $page['component']);
        $this->assertSame($status, $page['props']['status']);
    }

    public function test_an_unmatched_url_renders_the_error_page(): void
    {
        $this->assertRendersErrorPage($this->get('/no-such-page'), 404);
    }

    /**
     * The "web" group never runs for an unmatched URI, so HandleInertiaRequests
     * shares nothing. The page must still render — it reads auth optionally.
     */
    public function test_the_error_page_renders_without_shared_props(): void
    {
        $response = $this->get('/no-such-page');

        $this->assertArrayNotHasKey('auth', $response->viewData('page')['props']);
    }

    public function test_a_policy_denial_renders_the_error_page(): void
    {
        $user = User::factory()->withRole('User')->create();
        $other = User::factory()->withRole('User')->create();

        $monitor = Monitor::factory()->forUser($other)->create();

        $this->assertRendersErrorPage(
            $this->actingAs($user)->get(route('monitors.show', $monitor)),
            403,
        );
    }

    public function test_a_server_error_renders_the_error_page(): void
    {
        config(['app.debug' => false]);

        Route::middleware('web')->get('/__boom', fn () => throw new RuntimeException('boom'));

        $this->assertRendersErrorPage($this->get('/__boom'), 500);
    }

    /**
     * Swallowing the stack trace behind a pretty page would make local
     * debugging strictly worse than before.
     */
    public function test_a_server_error_is_left_alone_while_debugging(): void
    {
        config(['app.debug' => true]);

        Route::middleware('web')->get('/__boom', fn () => throw new RuntimeException('boom'));

        $response = $this->get('/__boom');

        $response->assertStatus(500);
        // Not a Laravel view at all with debug on, so assert on the body: the
        // branded page would have serialised the Error component into it.
        $response->assertDontSee('"component":"Error"', escape: false);
    }

    public function test_api_routes_still_answer_with_json(): void
    {
        $response = $this->getJson('/api/v1/monitors');

        $response->assertStatus(401)->assertJsonStructure(['message']);
    }

    public function test_a_missing_api_resource_answers_with_json(): void
    {
        $user = User::factory()->withRole('User')->create();

        $token = $user->createToken('test', ['monitors:read'])->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/monitors/'.fake()->uuid());

        $response->assertStatus(404)->assertHeader('content-type', 'application/json');
    }

    public function test_the_blade_fallbacks_render_without_built_assets(): void
    {
        foreach ([500, 503] as $status) {
            $html = view("errors.{$status}")->render();

            $this->assertStringContainsString((string) $status, $html);
            $this->assertStringNotContainsString('@vite', $html);
            // A built asset URL here would 404 during maintenance.
            $this->assertStringNotContainsString('/build/', $html);
        }
    }
}
