<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Policies\MonitorPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

#[UsePolicy(MonitorPolicy::class)]
class MonitorController extends Controller
{
    public function index(Request $request)
    {
        if($request->user()->cannot('viewAny', Monitor::class)) {
            abort(403);
        }

        $monitors = Monitor::query()
            ->where('created_by', Auth::id())
            ->with(['createdBy', 'monitorChecks' => function($query) {
                $query->latest('checked_at')->limit(1);
            }])
            ->paginate(15);

        return Inertia::render('monitors/Index', [
            'monitors' => $monitors,
        ]);
    }

    public function show(Request $request, Monitor $monitor) 
    {
        if ($request->user()->cannot('view', $monitor)) {
            abort(403);
        }

        $monitor->loadMissing('monitorCheck');

        return Inertia::render('monitors/Show', [
            'monitor' => $monitor
        ]);
    }
}
