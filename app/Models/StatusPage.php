<?php

namespace App\Models;

use App\StatusPages\StatusPageTheme;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'slug', 'title', 'description', 'is_published', 'theme'])]
class StatusPage extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'theme' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class)
            ->withPivot('sort_order')
            ->orderBy('sort_order');
    }

    /**
     * The page's house style, with every gap filled in. Pages saved before
     * theming existed store null and get the app's own look.
     *
     * Named apart from the `theme` column so it stays obvious which of the two
     * you are holding: the raw stored array, or the resolved value object.
     */
    public function resolvedTheme(): StatusPageTheme
    {
        return StatusPageTheme::fromArray($this->theme);
    }
}
