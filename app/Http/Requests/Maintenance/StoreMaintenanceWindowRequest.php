<?php

namespace App\Http\Requests\Maintenance;

use App\Models\MaintenanceWindow;

class StoreMaintenanceWindowRequest extends MaintenanceWindowRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MaintenanceWindow::class);
    }
}
