<?php

namespace Tests\Feature\Users;

use App\Actions\Users\CreateUser;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    private function action(): CreateUser
    {
        return app(CreateUser::class);
    }

    public function test_a_created_user_defaults_to_the_user_role(): void
    {
        $this->seed();

        $user = $this->action()->create(name: 'Ada', email: 'ada@example.test');

        $this->assertTrue($user->hasRole(Role::User->value));
        $this->assertTrue($user->can('monitors.create'));
    }

    public function test_an_explicit_role_is_honoured(): void
    {
        $this->seed();

        $user = $this->action()->create(
            name: 'Grace',
            email: 'grace@example.test',
            role: Role::Admin,
        );

        $this->assertTrue($user->hasRole(Role::Admin->value));
    }

    public function test_a_role_is_assigned_even_when_roles_were_never_seeded(): void
    {
        $user = $this->action()->create(name: 'Alan', email: 'alan@example.test');

        $this->assertTrue($user->hasRole(Role::User->value));
    }

    public function test_the_password_is_hashed_and_verification_is_opt_in(): void
    {
        $this->seed();

        $user = $this->action()->create(
            name: 'Katherine',
            email: 'katherine@example.test',
            password: 'secret-password',
        );

        $this->assertNotSame('secret-password', $user->password);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertNull($user->email_verified_at);
    }

    public function test_first_or_create_does_not_duplicate_an_existing_account(): void
    {
        $this->seed();

        $first = $this->action()->create(name: 'Edsger', email: 'e@example.test');
        $second = $this->action()->firstOrCreate(name: 'Someone', email: 'e@example.test');

        $this->assertTrue($first->is($second));
        $this->assertSame(1, User::where('email', 'e@example.test')->count());
    }

    public function test_registering_through_the_app_assigns_a_role(): void
    {
        $this->seed();

        $this->post(route('register.store'), [
            'name' => 'Registered',
            'email' => 'registered@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::where('email', 'registered@example.test')->firstOrFail();

        $this->assertTrue($user->hasRole(Role::User->value));
    }
}
