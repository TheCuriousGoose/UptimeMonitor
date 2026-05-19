<?php

namespace App\Http\Requests\Monitors;

use App\Models\Monitor;
use App\Rules\Cron;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Monitor::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['max:255', 'string'],
            'url' => ['url', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'timeout' => ['integer', 'min:1', 'max:300'],
            'check_interval' => ['string', new Cron],
        ];
    }
}
