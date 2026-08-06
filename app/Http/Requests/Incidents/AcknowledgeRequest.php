<?php

namespace App\Http\Requests\Incidents;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('acknowledge', $this->route('incident'));
    }

    public function rules(): array
    {
        return [
            'note' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
