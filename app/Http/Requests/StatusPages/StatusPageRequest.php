<?php

namespace App\Http\Requests\StatusPages;

use App\Models\StatusPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class StatusPageRequest extends FormRequest
{
    /**
     * The form submits a blank entry when no monitors are ticked so the list
     * can be cleared; drop it before the exists rule runs.
     */
    protected function prepareForValidation(): void
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
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => __('status_pages.validation.slug'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pageAttributes(): array
    {
        return $this->safe()->only(['title', 'slug', 'description', 'is_published']);
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
