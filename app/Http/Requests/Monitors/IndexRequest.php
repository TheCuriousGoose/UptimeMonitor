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
        ];
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
