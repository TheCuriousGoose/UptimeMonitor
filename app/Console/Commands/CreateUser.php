<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('make:user')]
#[Description('Create a new user')]
class CreateUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');

        $emailAlreadyExists = User::where('email', $email)->exists();

        if ($emailAlreadyExists) {
            $this->error('User with that email already exists!');
        }

        $password = $this->secret('Password');
        $passwordConfirmation = $this->secret('Confirm Password');

        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match.');

            return Command::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info('User created successfully.');
        $this->table([
            'Type', 'Value',
        ], [
            ['ID', $user->id],
            ['Name', $name],
            ['Email', $email],
        ]);
    }
}
