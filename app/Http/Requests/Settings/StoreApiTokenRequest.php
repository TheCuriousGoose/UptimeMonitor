<?php

namespace App\Http\Requests\Settings;

use App\Enums\ApiAbility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in(ApiAbility::values())],
            // Null means "never" — the form always renders it as an explicit
            // option rather than treating a blank field as the default.
            'expires_in_days' => ['nullable', 'integer', Rule::in([30, 90, 365])],
        ];
    }
}
