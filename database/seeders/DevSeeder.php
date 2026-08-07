<?php

namespace Database\Seeders;

use App\Actions\Users\CreateUser;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevSeeder extends Seeder
{
    /**
     * Run the database seeds for development.
     */
    public function run(): void
    {
        // Safety check — prevent accidental production runs
        if (app()->isProduction()) {
            $this->command->error('DevSeeder cannot run in production.');

            return;
        }

        app(CreateUser::class)->firstOrCreate(
            name: 'Admin User',
            email: 'admin@example.test',
            password: 'password',
            role: Role::SuperAdmin,
            verified: true,
        );

        $usersToGenerate = 10;
        $userCount = User::count();

        if ($userCount < $usersToGenerate) {
            User::factory($usersToGenerate - $userCount)->withRole(Role::User->value)->create();
        }

        // Generate fake monitors, check history, incidents, channels and status pages
        $this->call([
            MonitorSeeder::class,
            AlertingSeeder::class,
        ]);
    }
}
