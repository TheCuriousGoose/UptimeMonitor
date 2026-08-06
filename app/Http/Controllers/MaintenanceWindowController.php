<?php

namespace App\Http\Controllers;

use App\Http\Requests\Maintenance\StoreMaintenanceWindowRequest;
use App\Http\Requests\Maintenance\UpdateMaintenanceWindowRequest;
use App\Http\Resources\MaintenanceWindowResource;
use App\Http\Resources\MonitorResource;
use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use App\Policies\MaintenanceWindowPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

#[UsePolicy(MaintenanceWindowPolicy::class)]
class MaintenanceWindowController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', MaintenanceWindow::class);

        $windows = MaintenanceWindow::query()
            ->where('user_id', Auth::id())
            ->withCount('monitors')
            ->with('monitors')
            ->orderBy('name')
            ->get();

        return Inertia::render('maintenance/Index', [
            'windows' => MaintenanceWindowResource::collection($windows)->resolve(),
            'monitors' => MonitorResource::collection($this->userMonitors())->resolve(),
        ]);
    }

    public function store(StoreMaintenanceWindowRequest $request): RedirectResponse
    {
        $window = MaintenanceWindow::query()->create(
            $request->windowAttributes() + ['user_id' => $request->user()->id],
        );

        $this->syncMonitors($window, $request->monitorUuids());

        return back()->with('success', __('maintenance.messages.created'));
    }

    public function update(UpdateMaintenanceWindowRequest $request, MaintenanceWindow $maintenanceWindow): RedirectResponse
    {
        $maintenanceWindow->update($request->windowAttributes());

        $this->syncMonitors($maintenanceWindow, $request->monitorUuids());

        return back()->with('success', __('maintenance.messages.updated'));
    }

    public function destroy(MaintenanceWindow $maintenanceWindow): RedirectResponse
    {
        $this->authorize('delete', $maintenanceWindow);

        $maintenanceWindow->delete();

        return back()->with('success', __('maintenance.messages.deleted'));
    }

    /**
     * Resolved through forUser() rather than trusting the submitted uuids, so
     * a window cannot be pointed at somebody else's monitors.
     *
     * @param  array<int, string>  $uuids
     */
    private function syncMonitors(MaintenanceWindow $window, array $uuids): void
    {
        $ids = Monitor::query()
            ->forUser($window->user)
            ->whereIn('uuid', $uuids)
            ->pluck('id');

        $window->monitors()->sync($ids);
    }

    private function userMonitors()
    {
        return Monitor::query()->forUser(Auth::user())->orderBy('name')->get();
    }
}
