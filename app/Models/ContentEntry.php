<?php

namespace App\Models;

use App\Enums\ContentType;
use App\Support\SqlDialect;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'type', 'title', 'slug', 'excerpt', 'body', 'version',
    'category', 'sort_order', 'published_at', 'author_id',
])]
class ContentEntry extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'type' => ContentType::class,
            'published_at' => 'immutable_datetime',
            'sort_order' => 'integer',
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

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Published means "has a publish date that has arrived" — a future date is
     * a scheduled post, which must stay invisible until then.
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeOfType(Builder $query, ContentType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    /**
     * Docs are a hand-ordered manual; blog and changelog are newest-first.
     */
    public function scopeInReadingOrder(Builder $query, ContentType $type): Builder
    {
        return $type->isManuallyOrdered()
            ? $query->orderBy('category')->orderBy('sort_order')->orderBy('title')
            : $query->orderByDesc('published_at');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        $like = SqlDialect::like();

        return $query->where(function (Builder $q) use ($search, $like) {
            $q->where('title', $like, "%{$search}%")
                ->orWhere('slug', $like, "%{$search}%");
        });
    }
}
