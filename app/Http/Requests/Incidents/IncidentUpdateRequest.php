<?php

namespace App\Http\Requests\Incidents;

use App\Enums\IncidentUpdateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $update = $this->route('incident_update');

        return $update !== null
            ? $this->user()->can('update', $update)
            : $this->user()->can('comment', $this->route('incident'));
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'status' => ['sometimes', 'nullable', Rule::enum(IncidentUpdateStatus::class)],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function updateAttributes(): array
    {
        return [
            'body' => $this->string('body')->toString(),
            'status' => $this->input('status') ?: null,
            'is_public' => $this->boolean('is_public'),
        ];
    }
}
