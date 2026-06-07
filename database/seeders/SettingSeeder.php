<?php

namespace Database\Seeders;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Settings are defined here. They can only be added via this seeder.
     * Existing records are never overwritten so live values are preserved.
     */
    private array $settings = [
        [
            'key' => 'oauth.google',
            'group' => 'authentication',
            'label' => 'Google OAuth',
            'description' => 'Enable Google OAuth authentication',
            'type' => SettingType::Boolean,
            'value' => '0'
        ],
        [
            'key' => 'oauth.github',
            'group' => 'authentication',
            'label' => 'GitHub OAuth',
            'description' => 'Enable GitHub OAuth authentication',
            'type' => SettingType::Boolean,
            'value' => '0'
        ]
    ];

    public function run(): void
    {
        foreach ($this->settings as $definition) {
            $definition['type'] = $definition['type']->value;

            Setting::firstOrCreate(
                ['key' => $definition['key']],
                $definition,
            );
        }
    }
}
