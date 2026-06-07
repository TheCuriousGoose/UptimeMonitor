<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Always runs (production-safe)
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingSeeder::class,
        ]);

        // Dev only
        if (app()->isLocal()) {
            $this->call(DevSeeder::class);
        }
    }
}
