<?php

namespace App\Monitoring;

use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MaintenanceSchedule
{
    public function covers(Monitor $monitor, CarbonInterface $at): bool
    {
        return $this->windowsFor($monitor)->contains(
            fn (MaintenanceWindow $window) => $window->coversAt($at),
        );
    }

    /**
     * @return Collection<int, MaintenanceWindow>
     */
    public function windowsFor(Monitor $monitor)
    {
        return MaintenanceWindow::query()
            ->active()
            ->whereHas('monitors', fn ($query) => $query->whereKey($monitor->getKey()))
            ->get();
    }
}
