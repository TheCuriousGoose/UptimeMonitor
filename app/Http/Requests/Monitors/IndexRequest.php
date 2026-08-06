<?php

namespace App\Http\Requests\Monitors;

use App\Enums\MonitorStatus;
use App\Models\Monitor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Monitor::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(MonitorStatus::class)],
            'sort' => ['nullable', Rule::in(array_keys(Monitor::SORTS))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    public function sort(): ?string
    {
        $sort = (string) $this->input('sort');

        return array_key_exists($sort, Monitor::SORTS) ? $sort : null;
    }

    public function direction(): string
    {
        return $this->input('direction') === 'desc' ? 'desc' : 'asc';
    }

    public function search(): ?string
    {
        if (! $this->filled('search')) {
            return null;
        }

        // Escape LIKE wildcards so a literal % or _ does not widen the search.
        return str_replace(['%', '_'], ['\%', '\_'], trim($this->str('search')));
    }

    public function status(): ?MonitorStatus
    {
        return MonitorStatus::tryFrom((string) $this->input('status'));
    }
}
