<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use App\Models\MonitorCheck;
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
        if ($request->user()->cannot('viewAny', Monitor::class)) {
            abort(403);
        }

        $search = $request->filled('search')
            ? str_replace(['%', '_'], ['\%', '\_'], trim($request->search))
            : null;

        $monitors = Monitor::query()
            ->addSelect([
                'latest_is_up' => MonitorCheck::select('is_up')
                    ->whereColumn('monitor_id', 'monitors.id')
                    ->latest('checked_at')
                    ->limit(1)
            ])
            ->where('created_by', Auth::id())
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%")
                        ->orWhere('url', 'LIKE', "%$search%");
                });
            })
            ->orderBy('latest_is_up', 'asc')
            ->with(['createdBy', 'monitorChecks' => function ($query) {
                $query->latest('checked_at')->limit(1);
            }])
            ->paginate(15);

        return Inertia::render('monitors/Index', [
            'monitors' => $monitors->toResourceCollection(),
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
