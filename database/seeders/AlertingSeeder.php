<?php

namespace Database\Seeders;

use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Gives the first dev user a working channel and a public status page so
 * those screens are not empty on a fresh install.
 */
class AlertingSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        if (! $user) {
            return;
        }

        $email = NotificationChannel::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Ops email'],
            ['type' => 'email', 'config' => ['email' => $user->email]],
        );

        $monitors = Monitor::query()->where('created_by', $user->id)->limit(6)->get();

        foreach ($monitors as $monitor) {
            $monitor->notificationChannels()->syncWithoutDetaching([$email->id]);
        }

        $page = StatusPage::firstOrCreate(
            ['slug' => 'acme'],
            [
                'user_id' => $user->id,
                'title' => 'Acme Status',
                'description' => 'Live availability for our public services.',
                'is_published' => true,
            ],
        );

        $page->monitors()->sync(
            $monitors->values()->mapWithKeys(
                fn (Monitor $monitor, int $index) => [$monitor->id => ['sort_order' => $index]],
            )->all(),
        );
    }
}
