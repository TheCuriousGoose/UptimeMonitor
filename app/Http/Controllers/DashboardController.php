<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncidentResource;
use App\Http\Resources\MonitorResource;
use App\Models\Incident;
use App\Models\Monitor;
use App\Monitoring\UptimeStats;
use App\Onboarding\OnboardingProgress;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(UptimeStats $stats)
    {
        $user = Auth::user();
        $progress = OnboardingProgress::for($user);

        // An account with nothing in it has no dashboard to show — every tile
        // reads zero and every panel is empty. Send them somewhere that helps
        // instead. Skipping sets the flag, so this happens at most once.
        if (! $progress['has_monitor'] && ! $progress['dismissed'] && $user->can('create', Monitor::class)) {
            return to_route('onboarding.show');
        }

        $since = now()->subDay();

        $monitorIds = Monitor::query()->forUser($user)->select('id');

        return Inertia::render('Dashboard', [
            'onboarding' => $progress,
            'summary' => $stats->summaryForUser($user, $since),
            // resolve() keeps these as plain arrays; the pages expect lists,
            // not the "data" envelope a resource collection adds by default.
            'attention' => MonitorResource::collection(
                Monitor::query()
                    ->forUser($user)
                    ->where('is_active', true)
                    ->where('latest_is_up', false)
                    ->orderBy('status_changed_at')
                    ->limit(10)
                    ->get(),
            )->resolve(),
            'recentIncidents' => IncidentResource::collection(
                Incident::query()
                    ->whereIn('monitor_id', $monitorIds)
                    ->with('monitor')
                    ->orderByDesc('started_at')
                    ->limit(10)
                    ->get(),
            )->resolve(),
        ]);
    }
}
