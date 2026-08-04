<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Always runs (production-safe). ContentSeeder is create-only, so a
        // re-seed on deploy never overwrites edited docs or legal pages.
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingSeeder::class,
            ContentSeeder::class,
        ]);

        // Dev only
        if (app()->isLocal()) {
            $this->call(DevSeeder::class);
        }
    }
}
