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
            'value' => '0',
            'sort_order' => 0,
        ],
        [
            'key' => 'oauth.google.client_id',
            'parent_key' => 'oauth.google',
            'group' => 'authentication',
            'label' => 'Google client ID',
            'description' => 'From the Google Cloud console, under APIs & Services → Credentials',
            'type' => SettingType::String,
            'sort_order' => 0,
        ],
        [
            'key' => 'oauth.google.client_secret',
            'parent_key' => 'oauth.google',
            'group' => 'authentication',
            'label' => 'Google client secret',
            'description' => 'Stored encrypted and never shown again once saved',
            'type' => SettingType::Secret,
            'sort_order' => 1,
        ],
        [
            'key' => 'oauth.google.redirect',
            'parent_key' => 'oauth.google',
            'group' => 'authentication',
            'label' => 'Google redirect URL',
            'description' => 'Leave blank to use /auth/google/callback on this instance',
            'type' => SettingType::String,
            'sort_order' => 2,
        ],
        [
            'key' => 'oauth.github',
            'group' => 'authentication',
            'label' => 'GitHub OAuth',
            'description' => 'Enable GitHub OAuth authentication',
            'type' => SettingType::Boolean,
            'value' => '0',
            'sort_order' => 1,
        ],
        [
            'key' => 'oauth.github.client_id',
            'parent_key' => 'oauth.github',
            'group' => 'authentication',
            'label' => 'GitHub client ID',
            'description' => 'From Settings → Developer settings → OAuth Apps',
            'type' => SettingType::String,
            'sort_order' => 0,
        ],
        [
            'key' => 'oauth.github.client_secret',
            'parent_key' => 'oauth.github',
            'group' => 'authentication',
            'label' => 'GitHub client secret',
            'description' => 'Stored encrypted and never shown again once saved',
            'type' => SettingType::Secret,
            'sort_order' => 1,
        ],
        [
            'key' => 'oauth.github.redirect',
            'parent_key' => 'oauth.github',
            'group' => 'authentication',
            'label' => 'GitHub redirect URL',
            'description' => 'Leave blank to use /auth/github/callback on this instance',
            'type' => SettingType::String,
            'sort_order' => 2,
        ],
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
