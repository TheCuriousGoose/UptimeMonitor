<?php

namespace App\Http\Requests\Incidents;

use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRequest extends FormRequest
{
    public const STATUSES = ['ongoing', 'resolved'];

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Incident::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
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

    public function status(): ?string
    {
        $status = (string) $this->input('status');

        return in_array($status, self::STATUSES, true) ? $status : null;
    }
}
