<?php

namespace App\Models;

use App\Enums\SettingType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'group', 'parent_key', 'sort_order', 'label', 'description', 'type', 'value'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
            'sort_order' => 'integer',
        ];
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_key', 'key')->orderBy('sort_order');
    }

    public function isSecret(): bool
    {
        return $this->type->isSecret();
    }

    public function hasValue(): bool
    {
        return $this->value !== null && $this->value !== '';
    }

    public function toArray()
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'type' => $this->type,
            // A secret is reported as set or unset, never echoed back.
            'value' => $this->isSecret() ? null : $this->value,
            'has_value' => $this->hasValue(),
        ];
    }
}
