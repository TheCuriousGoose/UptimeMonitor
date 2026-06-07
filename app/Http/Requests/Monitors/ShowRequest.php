<?php

namespace App\Http\Requests\Monitors;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class ShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('monitor'));
    }

    public function rules(): array
    {
        return [
            'period' => ['sometimes', 'string', 'in:1h,24h,7d,30d'],
        ];
    }

    public function period(): string
    {
        return $this->string('period', '24h')->toString();
    }

    public function from(): CarbonImmutable
    {
        return match ($this->period()) {
            '1h' => now()->subHour(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };
    }
}
