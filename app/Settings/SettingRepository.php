<?php

namespace App\Settings;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

class SettingRepository
{
    private ?Collection $cache = null;

    /**
     * Get a setting value by key, cast to its declared type.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->load()->get($key);

        if ($setting === null) {
            return $default;
        }

        return $this->castValue($setting);
    }

    /**
     * Update the stored value of an existing setting.
     */
    public function set(string $key, mixed $value): void
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        $setting->update(['value' => $this->serializeValue($setting->type, $value)]);

        $this->cache = null;
    }

    /**
     * Return all settings as a flat collection of Setting models.
     */
    public function all(): Collection
    {
        return $this->load()->values();
    }

    /**
     * Return settings belonging to a specific group.
     */
    public function group(string $group): Collection
    {
        return $this->load()->filter(fn (Setting $s) => $s->group === $group)->values();
    }

    /**
     * Whether a provider is switched on *and* has usable credentials, so the
     * login screen never offers a button that can only 404.
     */
    public function oauthUsable(string $provider): bool
    {
        if (! $this->get("oauth.{$provider}", false)) {
            return false;
        }

        $stored = $this->childrenOf("oauth.{$provider}");

        return filled($stored->get('client_id') ?: config("services.{$provider}.client_id"))
            && filled($stored->get('client_secret') ?: config("services.{$provider}.client_secret"));
    }

    /**
     * Top-level authentication settings for the login screen. Child rows hold
     * credentials and are deliberately not shared with the browser.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function authenticationSettings(): Collection
    {
        return $this->group('authentication')
            ->filter(fn (Setting $s) => $s->parent_key === null)
            ->map(fn (Setting $s) => [
                'key' => $s->key,
                'label' => $s->label,
                'type' => $s->type->value,
                'value' => str_starts_with($s->key, 'oauth.')
                    ? ($this->oauthUsable(str($s->key)->afterLast('.')->toString()) ? '1' : '0')
                    : $s->value,
            ])
            ->values();
    }

    /**
     * Return all settings grouped by their group key.
     */
    public function grouped(): Collection
    {
        return $this->load()->values()->groupBy('group');
    }

    /**
     * Top-level settings, each with the children that hang off its key.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function tree(): Collection
    {
        $byParent = $this->load()->values()->groupBy('parent_key');

        return $this->load()->values()
            ->filter(fn (Setting $s) => $s->parent_key === null)
            ->sortBy([['group', 'asc'], ['sort_order', 'asc'], ['label', 'asc']])
            ->map(fn (Setting $s) => $this->present($s) + [
                'children' => $byParent->get($s->key, collect())
                    ->sortBy('sort_order')
                    ->map(fn (Setting $child) => $this->present($child))
                    ->values()
                    ->all(),
            ])
            ->values();
    }

    /**
     * Children of a parent key, keyed by the trailing segment of their key.
     *
     * @return Collection<string, mixed>
     */
    public function childrenOf(string $parentKey): Collection
    {
        return $this->load()->values()
            ->filter(fn (Setting $s) => $s->parent_key === $parentKey)
            ->mapWithKeys(fn (Setting $s) => [
                str($s->key)->afterLast('.')->toString() => $this->castValue($s),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Setting $setting): array
    {
        return [
            'id' => $setting->id,
            'key' => $setting->key,
            'group' => $setting->group,
            'parent_key' => $setting->parent_key,
            'label' => $setting->label,
            'description' => $setting->description,
            'type' => $setting->type->value,
            'value' => $setting->isSecret() ? null : $setting->value,
            'has_value' => $setting->hasValue(),
        ];
    }

    /**
     * Flush the in-memory cache so the next read re-queries the database.
     */
    public function flush(): void
    {
        $this->cache = null;
    }

    private function load(): Collection
    {
        if ($this->cache === null) {
            $this->cache = Setting::orderBy('group')->orderBy('label')->get()->keyBy('key');
        }

        return $this->cache;
    }

    private function castValue(Setting $setting): mixed
    {
        return match ($setting->type) {
            SettingType::Boolean => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            SettingType::Integer => (int) $setting->value,
            SettingType::Float => (float) $setting->value,
            SettingType::Json => json_decode($setting->value, true),
            SettingType::String => (string) ($setting->value ?? ''),
            SettingType::Secret => $this->decrypt($setting->value),
        };
    }

    private function serializeValue(SettingType $type, mixed $value): string
    {
        return match ($type) {
            SettingType::Boolean => $value ? '1' : '0',
            SettingType::Json => json_encode($value),
            SettingType::Secret => Crypt::encryptString((string) $value),
            default => (string) $value,
        };
    }

    /**
     * Tolerates a plaintext value so a secret seeded or edited outside the
     * app does not hard-fail the whole settings screen.
     */
    private function decrypt(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }
}
