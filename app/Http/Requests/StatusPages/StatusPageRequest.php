<?php

namespace App\Http\Requests\StatusPages;

use App\Enums\StatusPageMode;
use App\Models\StatusPage;
use App\Rules\FontFileUrl;
use App\StatusPages\StatusPageTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class StatusPageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->pruneMonitors();
        $this->pruneLinks();
    }

    /**
     * The form submits a blank entry when no monitors are ticked so the list
     * can be cleared; drop it before the exists rule runs.
     */
    private function pruneMonitors(): void
    {
        if (! $this->has('monitors')) {
            return;
        }

        $this->merge([
            'monitors' => array_values(array_filter(
                (array) $this->input('monitors'),
                fn ($uuid) => is_string($uuid) && $uuid !== '',
            )),
        ]);
    }

    /**
     * The link editor keeps an empty row on screen for the next entry. An
     * untouched row is not an error the owner should have to clear.
     */
    private function pruneLinks(): void
    {
        if (! is_array($links = $this->input('theme.links'))) {
            return;
        }

        $theme = (array) $this->input('theme', []);

        $theme['links'] = array_values(array_filter(
            $links,
            fn ($link) => is_array($link)
                && (trim((string) ($link['label'] ?? '')) !== '' || trim((string) ($link['url'] ?? '')) !== ''),
        ));

        $this->merge(['theme' => $theme]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('status_pages', 'slug')->ignore($this->currentPage()?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_published' => ['sometimes', 'boolean'],
            'monitors' => ['sometimes', 'array'],
            'monitors.*' => [
                'string',
                Rule::exists('monitors', 'uuid')->where('created_by', $this->user()->id),
            ],
            ...$this->themeRules(),
        ];
    }

    /**
     * Colours, lengths and the font stack are free form — a company's identity
     * rarely lands on a preset — so the rules here are about staying inside
     * something renderable, not about steering the choice.
     *
     * @return array<string, mixed>
     */
    private function themeRules(): array
    {
        $hex = 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';

        return [
            'theme' => ['sometimes', 'array'],
            'theme.mode' => ['nullable', Rule::enum(StatusPageMode::class)],
            'theme.font_family' => ['nullable', 'string', 'max:160'],
            'theme.font_url' => ['nullable', 'string', 'max:255', 'url:http,https', new FontFileUrl],
            'theme.radius' => ['nullable', 'integer', 'between:'.StatusPageTheme::MIN_RADIUS.','.StatusPageTheme::MAX_RADIUS],
            'theme.width' => ['nullable', 'integer', 'between:'.StatusPageTheme::MIN_WIDTH.','.StatusPageTheme::MAX_WIDTH],
            'theme.brand_color' => ['nullable', 'string', $hex],
            'theme.background' => ['nullable', 'string', $hex],
            'theme.foreground' => ['nullable', 'string', $hex],
            'theme.up_color' => ['nullable', 'string', $hex],
            'theme.down_color' => ['nullable', 'string', $hex],
            'theme.warning_color' => ['nullable', 'string', $hex],
            'theme.logo_url' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'theme.favicon_url' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'theme.footer_text' => ['nullable', 'string', 'max:255'],
            'theme.links' => ['nullable', 'array', 'max:'.StatusPageTheme::MAX_LINKS],
            'theme.links.*.label' => ['required_with:theme.links.*.url', 'string', 'max:40'],
            'theme.links.*.url' => ['required_with:theme.links.*.label', 'string', 'max:255', 'url:http,https'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => __('status_pages.validation.slug'),
            'theme.*_color.regex' => __('status_pages.validation.color'),
            'theme.background.regex' => __('status_pages.validation.color'),
            'theme.foreground.regex' => __('status_pages.validation.color'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pageAttributes(): array
    {
        $attributes = $this->safe()->only(['title', 'slug', 'description', 'is_published']);

        if ($this->has('theme')) {
            // Normalise through the value object so what lands in the column is
            // always a complete, already-sanitised theme rather than whatever
            // subset of keys this particular form happened to submit.
            $attributes['theme'] = StatusPageTheme::fromArray(
                (array) $this->safe()->input('theme', []),
            )->toArray();
        }

        return $attributes;
    }

    /**
     * @return array<int, string>
     */
    public function monitorUuids(): array
    {
        return array_values(array_filter((array) $this->safe()->input('monitors', [])));
    }

    protected function currentPage(): ?StatusPage
    {
        $page = $this->route('status_page');

        return $page instanceof StatusPage ? $page : null;
    }
}
