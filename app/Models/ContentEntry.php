<?php

namespace App\Models;

use App\Enums\ContentType;
use App\Models\Concerns\RoutesByUuid;
use App\Support\SqlDialect;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'type', 'title', 'slug', 'excerpt', 'body', 'version',
    'category', 'sort_order', 'published_at', 'author_id',
])]
class ContentEntry extends Model
{
    use HasFactory, RoutesByUuid;

    protected function casts(): array
    {
        return [
            'type' => ContentType::class,
            'published_at' => 'immutable_datetime',
            'sort_order' => 'integer',
        ];
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

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    #[Scope]
    protected function ofType(Builder $query, ContentType $type): void
    {
        $query->where('type', $type->value);
    }

    /**
     * Docs are a hand-ordered manual; blog and changelog are newest-first.
     */
    #[Scope]
    protected function inReadingOrder(Builder $query, ContentType $type): void
    {
        $type->isManuallyOrdered()
            ? $query->orderBy('category')->orderBy('sort_order')->orderBy('title')
            : $query->orderByDesc('published_at');
    }

    #[Scope]
    protected function search(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $like = SqlDialect::like();

        $query->where(fn (Builder $inner) => $inner
            ->where('title', $like, "%{$search}%")
            ->orWhere('slug', $like, "%{$search}%"));
    }
}
