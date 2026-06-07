<?php

namespace Database\Seeders;

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

        // Create a default admin user for testing
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'name' => 'Admin User',
        ]);

        // Create additional test users
        User::factory(10)->create();

        // Generate fake monitor and check data
        $this->call([
            MonitorSeeder::class,
            MonitorCheckSeeder::class,
        ]);
    }
}
