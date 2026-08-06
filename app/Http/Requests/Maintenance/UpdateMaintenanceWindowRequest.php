<?php

namespace App\Http\Requests\Maintenance;

class UpdateMaintenanceWindowRequest extends MaintenanceWindowRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('maintenance_window'));
    }
}
