<?php

namespace App\Enums;

use App\Rules\Cron;

enum MaintenanceRecurrence: string
{
    case Once = 'once';
    case Recurring = 'recurring';

    /**
     * Validation for the fields this mode actually uses, mirroring
     * MonitorType::configRules().
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return match ($this) {
            self::Once => [
                'starts_at' => ['required', 'date'],
                'ends_at' => ['required', 'date', 'after:starts_at'],
                'cron' => ['nullable'],
                'duration_minutes' => ['nullable'],
            ],
            self::Recurring => [
                'cron' => ['required', 'string', 'max:100', new Cron],
                'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
                'starts_at' => ['nullable'],
                'ends_at' => ['nullable'],
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
