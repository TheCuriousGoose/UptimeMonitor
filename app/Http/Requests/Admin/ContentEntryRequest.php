<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentType;
use App\Models\ContentEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

abstract class ContentEntryRequest extends FormRequest
{
    /**
     * A blank slug is derived from the title rather than rejected — the
     * common case is an author who never touches the field.
     */
    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug'));

        $this->merge([
            'slug' => Str::slug($slug !== '' ? $slug : (string) $this->input('title')),
        ]);
    }

    public function rules(): array
    {
        $entry = $this->route('entry');

        return [
            'type' => ['required', Rule::enum(ContentType::class)],
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required', 'string', 'max:200',
                // Slugs only need to be unique within their own type.
                Rule::unique(ContentEntry::class, 'slug')
                    ->where('type', $this->input('type'))
                    ->ignore($entry?->id),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'version' => ['nullable', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            // Null keeps it a draft; a future date schedules it.
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function entryAttributes(): array
    {
        $data = $this->safe()->only([
            'type', 'title', 'slug', 'excerpt', 'body',
            'version', 'category', 'sort_order', 'published_at',
        ]);

        $type = ContentType::tryFrom((string) $this->input('type'));

        // Drop fields that do not belong to this type, so a doc cannot carry
        // a stray version and a changelog cannot carry a category.
        if (! $type?->hasVersion()) {
            $data['version'] = null;
        }

        if (! $type?->hasCategory()) {
            $data['category'] = null;
        }

        return $data;
    }
}
