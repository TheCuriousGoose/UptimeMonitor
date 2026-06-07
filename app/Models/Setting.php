<?php

namespace App\Models;

use App\Enums\SettingType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'group', 'label', 'description', 'type', 'value'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
        ];
    }

    public function toArray()
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'type' => $this->type,
            'value' => $this->value
        ]; 
    }
}
