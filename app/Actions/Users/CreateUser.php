<?php

namespace App\Actions\Users;

use App\Enums\Role;
use App\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

class CreateUser
{
    /**
     * The single entry point for creating a user. Every caller goes through
     * here so an account can never end up without a role.
     */
    public function create(
        string $name,
        string $email,
        ?string $password = null,
        ?Role $role = null,
        bool $verified = false,
    ): User {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => $verified ? now() : null,
        ]);

        // firstOrCreate rather than a plain lookup so an instance whose roles
        // were never seeded still ends up with a real assignment.
        $user->syncRoles(RoleModel::firstOrCreate([
            'name' => ($role ?? Role::User)->value,
            'guard_name' => 'web',
        ]));

        return $user;
    }

    /**
     * Fetch by email or create, so callers that key on an external identity
     * (OAuth, dev login) still get the role assignment.
     */
    public function firstOrCreate(
        string $name,
        string $email,
        ?string $password = null,
        ?Role $role = null,
        bool $verified = false,
    ): User {
        return User::where('email', $email)->first()
            ?? $this->create($name, $email, $password, $role, $verified);
    }
}
