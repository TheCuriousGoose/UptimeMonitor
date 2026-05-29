<?php

namespace App\Http\Requests\Monitors;

use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Rules\Cron;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'string', 'max:255'],
            'type' => ['required', 'string', new Enum(MonitorType::class)],
            'is_active' => ['boolean'],
            'timeout' => ['required', 'integer', 'min:1', 'max:300'],
            'check_interval' => ['required', 'string', new Cron],
        ];
    }
}
