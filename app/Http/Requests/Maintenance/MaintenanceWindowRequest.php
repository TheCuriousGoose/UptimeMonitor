<?php

namespace App\Http\Requests\Maintenance;

use App\Enums\MaintenanceRecurrence;
use App\Models\MaintenanceWindow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class MaintenanceWindowRequest extends FormRequest
{
    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:100'],
            'recurrence' => ['required', Rule::enum(MaintenanceRecurrence::class)],
            'timezone' => ['required', 'timezone'],
            'is_active' => ['sometimes', 'boolean'],
            'monitors' => ['sometimes', 'array'],
            'monitors.*' => ['string', 'uuid'],
        ], $this->recurrence()?->rules() ?? []);
    }

    public function recurrence(): ?MaintenanceRecurrence
    {
        return MaintenanceRecurrence::tryFrom((string) $this->input('recurrence'));
    }

    /**
     * @return array<string, mixed>
     */
    public function windowAttributes(): array
    {
        $data = $this->safe()->except('monitors');

        // Blank the fields the other mode owns, so switching modes cannot
        // leave a stale cron sitting behind a one-off window.
        return match ($this->recurrence()) {
            MaintenanceRecurrence::Once => array_merge($data, ['cron' => null, 'duration_minutes' => null]),
            MaintenanceRecurrence::Recurring => array_merge($data, ['starts_at' => null, 'ends_at' => null]),
            default => $data,
        };
    }

    /**
     * @return array<int, string>
     */
    public function monitorUuids(): array
    {
        return array_values(array_filter((array) $this->input('monitors', [])));
    }

    public function window(): ?MaintenanceWindow
    {
        $window = $this->route('maintenance_window');

        return $window instanceof MaintenanceWindow ? $window : null;
    }
}
