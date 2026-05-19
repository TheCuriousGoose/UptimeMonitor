<?php

namespace App\Http\Controllers;

use App\Http\Requests\Monitors\IndexRequest;
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
            ->withLatestStatus()
            ->forUser(Auth::user())
            ->search($request->search())
            ->orderBy('latest_is_up', 'asc')
            ->paginate(15);

        return Inertia::render('monitors/Index', [
            'monitors' => $monitors->toResourceCollection(),
        ]);
    }

    public function show(Monitor $monitor)
    {
        $this->authorize('view', $monitor);

        $monitor->loadMissing('monitorCheck');

        return Inertia::render('monitors/Show', [
            'monitor' => $monitor
        ]);
    }

    public function create()
    {
        $this->authorize('create');

        return Inertia::render('monitors/Create');
    }
}
