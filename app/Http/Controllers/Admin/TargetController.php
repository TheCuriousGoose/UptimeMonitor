<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Monitor;
use App\Models\User;
use App\Models\VerifiedDomain;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

#[UsePolicy(UserPolicy::class)]
class TargetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $targets = Monitor::query()
            ->whereNotNull('target_domain')
            ->groupBy('target_domain')
            ->select('target_domain')
            ->selectRaw('COUNT(*) as monitor_count')
            ->selectRaw('COUNT(DISTINCT created_by) as account_count')
            ->selectRaw('SUM(CASE WHEN is_active = TRUE THEN 60.0 / interval_seconds ELSE 0 END) as requests_per_minute')
            ->orderByDesc('requests_per_minute')
            ->limit(100)
            ->get();

        $verified = VerifiedDomain::query()
            ->verified()
            ->whereIn('domain', $targets->pluck('target_domain'))
            ->pluck('domain')
            ->all();

        return Inertia::render('admin/Targets', [
            'targets' => $targets->map(fn ($row) => [
                'domain' => $row->target_domain,
                'monitor_count' => (int) $row->monitor_count,
                'account_count' => (int) $row->account_count,
                'requests_per_minute' => round((float) $row->requests_per_minute, 3),
                'verified' => in_array($row->target_domain, $verified, true),
            ]),
            'limits' => [
                'per_domain' => config('monitoring.abuse.max_requests_per_minute_per_domain'),
                'per_domain_per_user' => config('monitoring.abuse.max_requests_per_minute_per_domain_per_user'),
            ],
        ]);
    }

    public function show(Request $request, string $domain): Response
    {
        $this->authorize('viewAny', User::class);

        $monitors = Monitor::query()
            ->forDomain($domain)
            ->with('createdBy:id,name,email')
            ->orderByDesc('is_active')
            ->orderBy('interval_seconds')
            ->limit(200)
            ->get();

        return Inertia::render('admin/Target', [
            'domain' => $domain,
            'verified' => VerifiedDomain::query()->verified()->where('domain', $domain)->exists(),
            'monitors' => $monitors->map(fn (Monitor $monitor) => [
                'uuid' => $monitor->uuid,
                'name' => $monitor->name,
                'url' => $monitor->url,
                'type' => $monitor->type->value,
                'interval_seconds' => (int) $monitor->interval_seconds,
                'requests_per_minute' => round($monitor->requestsPerMinute(), 3),
                'is_active' => (bool) $monitor->is_active,
                'paused_reason' => $monitor->paused_reason,
                'owner' => [
                    'name' => $monitor->createdBy?->name,
                    'email' => $monitor->createdBy?->email,
                ],
            ]),
            'totals' => [
                'monitors' => $monitors->count(),
                'accounts' => $monitors->pluck('created_by')->unique()->count(),
                'requests_per_minute' => round(
                    $monitors->where('is_active', true)->sum(fn (Monitor $m) => $m->requestsPerMinute()),
                    3,
                ),
            ],
        ]);
    }

    public function destroy(Request $request, string $domain): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $paused = Monitor::query()
            ->forDomain($domain)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'paused_at' => now(),
                'paused_reason' => __('admin.targets.paused_by_admin'),
            ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('admin.targets.messages.paused', ['count' => $paused, 'domain' => $domain]),
        ]);

        return back();
    }
}
