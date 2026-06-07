<?php

namespace App\Http\Controllers;

use App\Enums\MonitorType;
use App\Http\Requests\Monitors\IndexRequest;
use App\Http\Requests\Monitors\StoreRequest;
use App\Models\Monitor;
use App\Policies\MonitorPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

#[UsePolicy(MonitorPolicy::class)]
class MonitorController extends Controller
{
    public function index(IndexRequest $request)
    {
        $monitors = Monitor::query()
            ->forUser(Auth::user())
            ->search($request->search())
            ->with(['createdBy'])
            ->orderBy('latest_is_up', 'asc')
            ->paginate(15);

        return Inertia::render('monitors/Index', [
            'monitors' => $monitors->toResourceCollection(),
        ]);
    }

    public function show(Monitor $monitor)
    {
        $this->authorize('view', $monitor);

        $monitor->loadMissing('monitorChecks');

        return Inertia::render('monitors/Show', [
            'monitor' => $monitor,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Monitor::class);

        return Inertia::render('monitors/Create', [
            'types' => $this->getMonitorTypes()
        ]);
    }

    public function store(StoreRequest $request)
    {
        $user = $request->user();
        $monitor = $user->monitors()->create($request->validated());

        return to_route('monitors.show', $monitor)->with('success', __('monitors.messages.created.success'));
    }

    public function edit(Monitor $monitor) {
        $this->authorize('update', $monitor);

        return Inertia::render('monitors/Edit', [
            'monitor' => $monitor,
            'types' => $this->getMonitorTypes()
        ]);
    }

    private function getMonitorTypes(): array
    {
        return array_column(MonitorType::cases(), 'value');
    }
}
