<?php

namespace App\Settings;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Support\Collection;

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
        return $this->load()->filter(fn(Setting $s) => $s->group === $group)->values();
    }

    /**
     * Return all settings grouped by their group key.
     */
    public function grouped(): Collection
    {
        return $this->load()->values()->groupBy('group');
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
            SettingType::Float   => (float) $setting->value,
            SettingType::Json    => json_decode($setting->value, true),
            SettingType::String  => (string) ($setting->value ?? ''),
        };
    }

    private function serializeValue(SettingType $type, mixed $value): string
    {
        return match ($type) {
            SettingType::Boolean => $value ? '1' : '0',
            SettingType::Json    => json_encode($value),
            default              => (string) $value,
        };
    }
}
