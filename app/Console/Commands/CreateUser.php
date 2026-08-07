<?php

namespace App\Console\Commands;

use App\Actions\Users\CreateUser as CreateUserAction;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('make:user')]
#[Description('Create a new user')]
class CreateUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CreateUserAction $createUser)
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');

        if (User::where('email', $email)->exists()) {
            $this->error('User with that email already exists!');

            return Command::FAILURE;
        }

        $password = $this->secret('Password');
        $passwordConfirmation = $this->secret('Confirm Password');

        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match.');

            return Command::FAILURE;
        }

        $role = Role::from($this->choice(
            'Role',
            array_map(fn (Role $r) => $r->value, Role::cases()),
            Role::User->value,
        ));

        $user = $createUser->create(
            name: $name,
            email: $email,
            password: $password,
            role: $role,
            verified: true,
        );

        $this->info('User created successfully.');
        $this->table([
            'Type', 'Value',
        ], [
            ['ID', $user->id],
            ['Name', $name],
            ['Email', $email],
            ['Role', $role->value],
        ]);

        return Command::SUCCESS;
    }
}
