<?php

namespace App\Http\Requests\Monitors;

use App\Models\Monitor;
use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Monitor::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function search(): ?string
    {
        if (! $this->filled('search')) {
            return null;
        }

        return str_replace(['%', '_'], ['\%', '\_'], trim($this->str('search')));
    }
}
