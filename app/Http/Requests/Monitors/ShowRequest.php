<?php

namespace App\Http\Requests\Monitors;

use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;

class ShowRequest extends FormRequest
{
    public const PERIODS = ['1h', '24h', '7d', '30d', '90d'];

    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('monitor'));
    }

    public function rules(): array
    {
        return [
            'period' => ['sometimes', 'string', 'in:'.implode(',', self::PERIODS)],
        ];
    }

    public function period(): string
    {
        $period = $this->string('period', '24h')->toString();

        return in_array($period, self::PERIODS, true) ? $period : '24h';
    }

    public function from(): CarbonInterface
    {
        return match ($this->period()) {
            '1h' => now()->subHour(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDay(),
        };
    }
}
